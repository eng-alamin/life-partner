<?php

declare(strict_types=1);

namespace App\Livewire\Notifications;

use App\Actions\Phone\ConfirmPhoneVerification;
use App\Actions\Phone\RemovePhone;
use App\Actions\Phone\StartPhoneVerification;
use App\Enums\NotificationCategory;
use App\Enums\OtpResult;
use App\Exceptions\OtpThrottledException;
use App\Exceptions\SmsDeliveryException;
use App\Models\User;
use App\Services\Notification\NotificationPreferences;
use App\Services\Sms\SmsGateway;
use App\Support\SmsNumber;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Email and SMS switches for each group, and the member's mobile number.
 *
 * The in-app list cannot be switched off, and payment messages are mandatory. The
 * browser's values are never trusted: only groups the member may see are read, each
 * as a plain yes or no. The full number is never sent back to the browser, only a
 * masked form; the member re-types it to change it.
 */
#[Layout('layouts::app')]
class NotificationSettings extends Component
{
    /** @var array<string, bool> */
    public array $email = [];

    /** @var array<string, bool> */
    public array $sms = [];

    public string $phone = '';

    public string $code = '';

    public string $notice = '';

    public string $phoneNotice = '';

    public function mount(NotificationPreferences $preferences): void
    {
        $user = $this->user();
        $emailMap = $preferences->emailMap($user);
        $smsMap = $preferences->smsMap($user);

        foreach (NotificationCategory::forUser($user) as $category) {
            $this->email[$category->value] = $emailMap[$category->value] ?? true;

            if ($category->sendsSms()) {
                $this->sms[$category->value] = $smsMap[$category->value] ?? true;
            }
        }
    }

    public function save(NotificationPreferences $preferences): void
    {
        $user = $this->user();

        foreach (NotificationCategory::forUser($user) as $category) {
            if ($category->isMandatory()) {
                continue;
            }

            $preferences->setEmail($user, $category, (bool) ($this->email[$category->value] ?? false));

            if ($category->sendsSms()) {
                $preferences->setSms($user, $category, (bool) ($this->sms[$category->value] ?? false));
            }
        }

        $this->notice = __('notifications.settings.saved');
    }

    /** Saves the typed number and sends a code to it. */
    public function sendCode(StartPhoneVerification $start): void
    {
        $this->validate(['phone' => ['required', 'string', 'max:25']]);

        $this->startFor($start, $this->phone);
    }

    /** Sends a new code to the number that is already saved (waiting for its code). */
    public function resendCode(StartPhoneVerification $start): void
    {
        $user = $this->user();

        if ($user->phone === null || $user->hasVerifiedPhone()) {
            return;
        }

        $this->startFor($start, (string) $user->phone);
    }

    public function confirmCode(ConfirmPhoneVerification $confirm): void
    {
        $user = $this->user();

        $this->validate(['code' => ['required', 'string', 'max:12']]);

        $key = 'verify-phone:'.$user->getKey();

        if (RateLimiter::tooManyAttempts($key, 15)) {
            $this->addError('code', __('account.throttled_minutes', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)]));

            return;
        }

        $result = $confirm->handle($user, $this->code);

        if ($result !== OtpResult::Verified) {
            RateLimiter::hit($key, 900);
            $this->addError('code', __('account.otp.'.Str::snake($result->name)));
            $this->code = '';

            return;
        }

        RateLimiter::clear($key);

        $this->code = '';
        $this->phone = '';
        $this->phoneNotice = __('sms.settings.verified_notice');
    }

    public function removePhone(RemovePhone $remove): void
    {
        $remove->handle($this->user());

        $this->code = '';
        $this->phone = '';
        $this->resetErrorBag();
        $this->phoneNotice = __('sms.settings.removed_notice');
    }

    public function render()
    {
        $user = $this->user();
        $number = (string) $user->phone;
        $smsReady = app(SmsGateway::class)->isReady();

        return view('livewire.notifications.notification-settings', [
            'categories' => NotificationCategory::forUser($user),
            'phoneVerified' => $user->hasVerifiedPhone(),
            'phoneWaiting' => $number !== '' && ! $user->hasVerifiedPhone(),
            'maskedPhone' => $number === '' ? '' : SmsNumber::mask($number),
            'smsReady' => $smsReady,
        ])->title(__('notifications.settings.title'));
    }

    private function startFor(StartPhoneVerification $start, string $input): void
    {
        $this->resetErrorBag();
        $this->phoneNotice = '';

        try {
            $start->handle($this->user(), $input);
        } catch (OtpThrottledException $exception) {
            $this->addError('phone', __('account.otp.throttled', ['seconds' => $exception->retryAfterSeconds]));

            return;
        } catch (SmsDeliveryException) {
            $this->addError('phone', __('sms.errors.send_failed'));

            return;
        }

        $this->phone = '';
        $this->phoneNotice = $this->user()->hasVerifiedPhone()
            ? __('sms.settings.verified_notice')
            : __('sms.settings.code_sent');
    }

    private function user(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
