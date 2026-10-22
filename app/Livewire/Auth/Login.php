<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::auth')]
class Login extends Component
{
    private const MAX_ATTEMPTS = 5;

    private const LOCKOUT_SECONDS = 900;

    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->email = Str::lower(trim($this->email));

        $this->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $key = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'email' => __('account.throttled_minutes', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)]),
            ]);
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($key, self::LOCKOUT_SECONDS);

            throw ValidationException::withMessages(['email' => __('account.login.failed')]);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->isActive()) {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();

            throw ValidationException::withMessages(['email' => __('account.login.suspended')]);
        }

        RateLimiter::clear($key);
        session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->save();

        // The saved language wins over whatever the browser session had.
        if (in_array($user->locale, array_keys(config('lifepartner.locales')), true)) {
            session()->put('locale', $user->locale);
        }

        if (! $user->hasVerifiedEmail()) {
            $this->redirectRoute('verification.notice');

            return;
        }

        $this->redirectIntended(route('dashboard'));
    }

    private function throttleKey(): string
    {
        return 'login:'.Str::lower($this->email).'|'.request()->ip();
    }

    public function render()
    {
        return view('livewire.auth.login')->title(__('account.login.title'));
    }
}
