<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\NotificationType;
use App\Enums\SmsStatus;
use App\Models\SmsMessage;
use App\Services\Sms\SmsGateway;
use App\Services\Sms\SmsResult;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends one queued SMS. It is given only the id of an sms_messages row.
 *
 * The row is claimed with one atomic update (queued to sending), so a retry or a
 * duplicate job can never send the same SMS twice. The number is read (decrypted)
 * and the text is built only here, in the member's own language.
 */
final class SendSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    /** @var list<int> seconds */
    public array $backoff = [60, 300];

    public function __construct(public readonly int $smsMessageId) {}

    public function handle(SmsGateway $gateway): void
    {
        $claimed = SmsMessage::query()
            ->whereKey($this->smsMessageId)
            ->where('status', SmsStatus::Queued->value)
            ->update([
                'status' => SmsStatus::Sending->value,
                'attempts' => DB::raw('attempts + 1'),
                'updated_at' => now(),
            ]);

        if ($claimed === 0) {
            return;
        }

        $sms = SmsMessage::query()->with('user')->find($this->smsMessageId);
        $user = $sms?->user;
        $type = $sms === null ? null : NotificationType::tryFrom($sms->type);

        if ($sms === null || $user === null || $type === null || ! $type->sendsSms()
            || ! $user->isActive() || ! $user->hasVerifiedPhone()) {
            $sms?->forceFill(['status' => SmsStatus::Skipped->value])->save();

            return;
        }

        $text = __('sms.types.'.$type->langKey(), ['app' => config('app.name')], $user->preferredLocale());

        $result = $gateway->send((string) $user->phone, $text);

        if ($result->ok) {
            $sms->forceFill([
                'status' => SmsStatus::Sent->value,
                'provider_ref' => $result->reference,
                'error_code' => null,
            ])->save();

            return;
        }

        if ($result->retryable && $this->attempts() < $this->tries) {
            $sms->forceFill(['status' => SmsStatus::Queued->value, 'error_code' => $result->errorCode])->save();
            $this->release($this->backoff[$this->attempts() - 1] ?? 300);

            return;
        }

        $this->markFailed($sms, $result);
    }

    /** Called by the queue when the job dies without a clean ending (crash, timeout). */
    public function failed(Throwable $exception): void
    {
        SmsMessage::query()
            ->whereKey($this->smsMessageId)
            ->whereIn('status', [SmsStatus::Queued->value, SmsStatus::Sending->value])
            ->update(['status' => SmsStatus::Failed->value, 'updated_at' => now()]);
    }

    private function markFailed(SmsMessage $sms, SmsResult $result): void
    {
        $sms->forceFill([
            'status' => SmsStatus::Failed->value,
            'error_code' => $result->errorCode,
        ])->save();

        // Out of balance affects everyone: tell the log, but only once an hour.
        if ($result->errorCode === SmsResult::INSUFFICIENT_BALANCE && Cache::add('sms:balance-alert', 1, 3600)) {
            Log::critical('sms.balance_empty: the SMS provider rejected a message because the balance is empty.');
        }
    }
}
