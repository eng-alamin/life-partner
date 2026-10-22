<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Enums\OtpPurpose;
use App\Enums\OtpResult;
use App\Exceptions\OtpThrottledException;
use App\Models\User;
use App\Services\Otp\OtpService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Two steps in one component: "request" (enter email, get a code) and
 * "reset" (enter code and new password). The screen never reveals whether an
 * email is registered.
 */
#[Layout('layouts::auth')]
class ForgotPassword extends Component
{
    public string $step = 'request';

    public string $email = '';

    public string $code = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function sendCode(OtpService $otp): void
    {
        $this->email = Str::lower(trim($this->email));

        $this->validate(['email' => ['required', 'string', 'email:rfc', 'max:255']]);

        $key = 'forgot:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->addError('email', __('account.throttled_minutes', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)]));

            return;
        }

        RateLimiter::hit($key, 900);

        $user = User::query()->where('email', $this->email)->first();

        if ($user !== null && $user->isActive()) {
            try {
                $otp->issue($user, OtpPurpose::Reset);
            } catch (OtpThrottledException) {
                // Same screen either way, so nothing is revealed.
            }
        }

        $this->resetErrorBag();
        $this->step = 'reset';
    }

    public function resetPassword(OtpService $otp): void
    {
        $this->email = Str::lower(trim($this->email));

        $this->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'code' => ['required', 'string', 'max:12'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $key = 'reset:'.$this->email.'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->addError('code', __('account.throttled_minutes', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)]));

            return;
        }

        $user = User::query()->where('email', $this->email)->first();

        $result = ($user !== null && $user->isActive())
            ? $otp->verify($user, OtpPurpose::Reset, $this->code)
            : OtpResult::Invalid;

        if ($result !== OtpResult::Verified) {
            RateLimiter::hit($key, 900);
            $this->addError('code', __('account.otp.'.Str::snake($result->name)));
            $this->code = '';

            return;
        }

        RateLimiter::clear($key);

        $user->forceFill([
            'password' => $this->password,
            'remember_token' => Str::random(60),
        ])->save();

        // The emailed code proves the person owns this address.
        $user->markEmailAsVerified();

        // Sign the account out everywhere else (database session driver only).
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->getKey())->delete();
        }

        session()->flash('status', __('account.reset.success'));

        $this->redirectRoute('login');
    }

    public function backToRequest(): void
    {
        $this->reset('step', 'code', 'password', 'password_confirmation');
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.auth.forgot-password')->title(__('account.reset.title'));
    }
}
