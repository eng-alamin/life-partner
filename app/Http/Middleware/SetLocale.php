<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolve($request);

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }

    /**
     * Order: session choice, then cookie, then the configured default.
     * Only locales listed in config/lifepartner.php are ever accepted.
     */
    private function resolve(Request $request): string
    {
        $supported = array_keys(config('lifepartner.locales'));

        $candidates = [
            $request->hasSession() ? $request->session()->get('locale') : null,
            $request->cookie(config('lifepartner.locale_cookie')),
            config('lifepartner.default_locale'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && in_array($candidate, $supported, true)) {
                return $candidate;
            }
        }

        return $supported[0];
    }
}
