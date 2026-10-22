<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;

/** Steps that follow every successful sign-in (call after Auth::login). */
class LoginFinisher
{
    public function finish(User $user): void
    {
        session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->save();

        if (in_array($user->locale, array_keys(config('lifepartner.locales')), true)) {
            session()->put('locale', $user->locale);
        }
    }
}
