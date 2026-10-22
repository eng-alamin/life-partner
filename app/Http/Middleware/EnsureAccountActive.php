<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out an account that was suspended while its owner was still logged in.
 * Registered as the "account.active" route middleware alias, used on every
 * signed-in route. (SuspendUser also deletes the stored sessions; this covers
 * session drivers that cannot be searched by user.)
 */
class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->isActive()) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['email' => __('account.login.suspended')]);
        }

        return $next($request);
    }
}
