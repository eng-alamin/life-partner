<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\SocialProvider;
use App\Services\Auth\LoginFinisher;
use App\Services\Auth\SocialAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

class SocialAuthController extends Controller
{
    /** {provider} is bound to the SocialProvider enum, so any other value is a 404. */
    public function redirect(SocialProvider $provider): SymfonyRedirect
    {
        abort_unless($provider->isConfigured(), 404);

        return Socialite::driver($provider->value)->redirect();
    }

    public function callback(
        Request $request,
        SocialProvider $provider,
        SocialAuthService $service,
        LoginFinisher $finisher,
    ): RedirectResponse {
        abort_unless($provider->isConfigured(), 404);

        if ($request->filled('error')) {
            return $this->failed();
        }

        try {
            $social = Socialite::driver($provider->value)->user();
        } catch (InvalidStateException) {
            return $this->failed();
        } catch (Throwable $e) {
            report($e);

            return $this->failed();
        }

        $outcome = $service->handle($provider, $social);

        if ($outcome->needsEmailProof) {
            $request->session()->put('social_pending', [
                'provider' => $provider->value,
                'provider_user_id' => (string) $social->getId(),
                'name' => $service->displayName($social, $social->getEmail()),
                'email' => (string) $social->getEmail(),
            ]);

            return redirect()->route('social.complete');
        }

        if ($outcome->registrationClosed) {
            return redirect()->route('login')->with('error', __('site.registration.closed_short'));
        }

        if ($outcome->user === null) {
            return redirect()->route('login')->with('error', __('account.social.unavailable'));
        }

        Auth::login($outcome->user);
        $finisher->finish($outcome->user);

        if (! $outcome->user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->intended(route('dashboard'));
    }

    private function failed(): RedirectResponse
    {
        return redirect()->route('login')->with('error', __('account.social.cancelled'));
    }
}
