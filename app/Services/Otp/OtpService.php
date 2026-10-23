<?php

declare(strict_types=1);

namespace App\Services\Otp;

use App\Enums\OtpPurpose;
use App\Enums\OtpResult;
use App\Exceptions\OtpThrottledException;
use App\Models\OtpCode;
use App\Models\User;
use App\Support\Digits;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Creates, delivers and checks one-time codes. It does not know how a code is
 * delivered (that is the OtpChannel's job). Codes are stored only as an HMAC
 * hash, never in plain text.
 */
class OtpService
{
    public const LENGTH = 6;

    public const TTL_MINUTES = 5;

    public const MAX_ATTEMPTS = 5;

    public const RESEND_COOLDOWN_SECONDS = 60;

    public const MAX_SENDS_PER_HOUR = 5;

    public function __construct(private readonly OtpChannel $channel) {}

    /**
     * @throws OtpThrottledException when the resend cooldown or hourly cap is hit
     */
    public function issue(User $user, OtpPurpose $purpose): void
    {
        $this->guardThrottle($user, $purpose);

        $code = str_pad((string) random_int(0, 10 ** self::LENGTH - 1), self::LENGTH, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($user, $purpose, $code): void {
            // Only the newest code is valid: retire older unused ones.
            $this->scope($user, $purpose)->whereNull('consumed_at')->update(['consumed_at' => now()]);

            OtpCode::query()->create([
                'user_id' => $user->getKey(),
                'channel' => $this->channel->name(),
                'purpose' => $purpose->value,
                'code_hash' => $this->hash($user, $purpose, $code),
                'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            ]);
        });

        $this->channel->send($user, $code, $purpose);
    }

    public function verify(User $user, OtpPurpose $purpose, string $input): OtpResult
    {
        $code = Digits::normalize(trim($input)) ?? '';

        if (preg_match('/^\d{'.self::LENGTH.'}$/', $code) !== 1) {
            return OtpResult::Invalid;
        }

        return DB::transaction(function () use ($user, $purpose, $code): OtpResult {
            $otp = $this->scope($user, $purpose)
                ->whereNull('consumed_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($otp === null || $otp->expires_at->isPast()) {
                return OtpResult::Expired;
            }

            if ($otp->attempts >= self::MAX_ATTEMPTS) {
                return OtpResult::TooManyAttempts;
            }

            if (hash_equals($otp->code_hash, $this->hash($user, $purpose, $code))) {
                $otp->forceFill(['consumed_at' => now()])->save();

                return OtpResult::Verified;
            }

            $otp->increment('attempts');

            return $otp->attempts >= self::MAX_ATTEMPTS ? OtpResult::TooManyAttempts : OtpResult::Invalid;
        });
    }

    private function guardThrottle(User $user, OtpPurpose $purpose): void
    {
        $latest = $this->scope($user, $purpose)->latest('id')->first();

        if ($latest !== null) {
            $wait = $latest->created_at->timestamp + self::RESEND_COOLDOWN_SECONDS - now()->timestamp;

            if ($wait > 0) {
                throw new OtpThrottledException($wait);
            }
        }

        $recent = $this->scope($user, $purpose)
            ->where('created_at', '>=', now()->subHour())
            ->orderBy('created_at')
            ->get(['created_at']);

        if ($recent->count() >= self::MAX_SENDS_PER_HOUR) {
            $wait = $recent->first()->created_at->timestamp + 3600 - now()->timestamp;

            throw new OtpThrottledException(max(1, $wait));
        }
    }

    /** @return Builder<OtpCode> */
    private function scope(User $user, OtpPurpose $purpose): Builder
    {
        return OtpCode::query()
            ->where('user_id', $user->getKey())
            ->where('purpose', $purpose->value)
            ->where('channel', $this->channel->name());
    }

    private function hash(User $user, OtpPurpose $purpose, string $code): string
    {
        return hash_hmac('sha256', $user->getKey().'|'.$purpose->value.'|'.$code, (string) config('app.key'));
    }
}
