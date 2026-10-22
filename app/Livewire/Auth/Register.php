<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Enums\OtpPurpose;
use App\Exceptions\OtpThrottledException;
use App\Models\User;
use App\Services\Otp\OtpService;
use App\Services\Settings\SiteSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::auth')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register(OtpService $otp, SiteSettings $settings): void
    {
        // Checked on the server for every call, not only when the page is drawn.
        if (! $settings->registrationOpen()) {
            throw ValidationException::withMessages(['email' => __('site.registration.closed_short')]);
        }

        $this->throttle();

        $this->name = trim((string) preg_replace('/\s+/u', ' ', $this->name));
        $this->email = Str::lower(trim($this->email));

        // An abandoned social sign-up leaves a password-less, never-used shell
        // account. It must not block the real owner of the address.
        User::query()
            ->where('email', $this->email)
            ->whereNull('password')
            ->whereNull('email_verified_at')
            ->whereNull('last_login_at')
            ->forceDelete();

        $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:100', "regex:/^[\p{L}\p{M}\s.'\-]+$/u"],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $user = User::query()->create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'locale' => app()->getLocale(),
        ]);

        Auth::login($user);
        session()->regenerate();

        try {
            $otp->issue($user, OtpPurpose::Verify);
        } catch (OtpThrottledException) {
            // A brand-new account cannot be throttled; nothing to do.
        }

        $this->reset('password', 'password_confirmation');

        $this->redirectRoute('verification.notice');
    }

    /** Limits sign-ups per IP (20 an hour; shared mobile networks are common in Bangladesh). */
    private function throttle(): void
    {
        $key = 'register:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 20)) {
            throw ValidationException::withMessages([
                'email' => __('account.throttled_minutes', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)]),
            ]);
        }

        RateLimiter::hit($key, 3600);
    }

    public function render()
    {
        return view('livewire.auth.register', ['open' => app(SiteSettings::class)->registrationOpen()])
            ->title(__('account.register.title'));
    }
}
