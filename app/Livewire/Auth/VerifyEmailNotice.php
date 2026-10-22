<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Enums\OtpPurpose;
use App\Enums\OtpResult;
use App\Exceptions\OtpThrottledException;
use App\Services\Otp\OtpService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::auth')]
class VerifyEmailNotice extends Component
{
    public string $code = '';

    public bool $resent = false;

    public function mount(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectRoute('dashboard');
        }
    }

    public function verify(OtpService $otp): void
    {
        $user = Auth::user();

        $this->validate(['code' => ['required', 'string', 'max:12']]);

        $key = 'verify-email:'.$user->getKey();

        if (RateLimiter::tooManyAttempts($key, 15)) {
            $this->addError('code', __('account.throttled_minutes', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)]));

            return;
        }

        $result = $otp->verify($user, OtpPurpose::Verify, $this->code);

        if ($result !== OtpResult::Verified) {
            RateLimiter::hit($key, 900);
            $this->addError('code', __('account.otp.'.Str::snake($result->name)));
            $this->code = '';

            return;
        }

        RateLimiter::clear($key);
        $user->markEmailAsVerified();

        session()->flash('status', __('account.verify.success'));

        $this->redirectRoute('dashboard');
    }

    public function resend(OtpService $otp): void
    {
        try {
            $otp->issue(Auth::user(), OtpPurpose::Verify);
        } catch (OtpThrottledException $e) {
            $this->addError('code', __('account.otp.throttled', ['seconds' => $e->retryAfterSeconds]));

            return;
        }

        $this->resetErrorBag();
        $this->resent = true;
    }

    public function render()
    {
        $email = (string) Auth::user()->email;
        $at = strpos($email, '@');
        $masked = $at === false ? $email : Str::mask($email, '*', 1, max(0, $at - 1));

        return view('livewire.auth.verify-email-notice', ['maskedEmail' => $masked])
            ->title(__('account.verify.title'));
    }
}
