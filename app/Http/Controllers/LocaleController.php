<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(array_keys(config('lifepartner.locales')))],
        ]);

        $request->session()->put('locale', $validated['locale']);

        Cookie::queue(
            config('lifepartner.locale_cookie'),
            $validated['locale'],
            config('lifepartner.locale_cookie_minutes')
        );

        // Signed-in members keep their language across devices.
        if ($request->user() !== null) {
            $request->user()->forceFill(['locale' => $validated['locale']])->save();
        }

        return redirect()->back(fallback: route('home'));
    }
}
