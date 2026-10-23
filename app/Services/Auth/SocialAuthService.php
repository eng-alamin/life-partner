<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\SocialProvider;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Settings\SiteSettings;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialUser;

/**
 * Decides what a provider sign-in means. Only Google's own "email_verified"
 * flag is trusted. Facebook emails are never trusted, so a Facebook sign-in
 * can never be linked to an existing account without an emailed code.
 */
class SocialAuthService
{
    public function __construct(private readonly SiteSettings $settings) {}

    public function handle(SocialProvider $provider, SocialUser $social): SocialOutcome
    {
        $providerUserId = (string) $social->getId();

        $linked = SocialAccount::query()
            ->with('user')
            ->where('provider', $provider->value)
            ->where('provider_user_id', $providerUserId)
            ->first();

        if ($linked !== null) {
            return $linked->user !== null && $linked->user->isActive()
                ? SocialOutcome::login($linked->user)
                : SocialOutcome::unavailable();
        }

        $email = filled($social->getEmail()) ? Str::lower(trim((string) $social->getEmail())) : null;

        if ($email === null || ! $this->emailIsTrusted($provider, $social)) {
            return SocialOutcome::needsEmailProof();
        }

        $user = User::query()->where('email', $email)->first();

        if ($user !== null && ! $user->isActive()) {
            return SocialOutcome::unavailable();
        }

        // An existing account may still be linked and signed in. Only a brand-new account is refused.
        if ($user === null && ! $this->settings->registrationOpen()) {
            return SocialOutcome::registrationClosed();
        }

        $user ??= User::query()->create([
            'name' => $this->displayName($social, $email),
            'email' => $email,
            'password' => null,
            'locale' => app()->getLocale(),
        ])->refresh(); // load DB defaults such as status = active

        $user->markEmailAsVerified();

        SocialAccount::query()->firstOrCreate(
            ['provider' => $provider->value, 'provider_user_id' => $providerUserId],
            ['user_id' => $user->getKey()],
        );

        return SocialOutcome::login($user);
    }

    public function displayName(SocialUser|array $social, ?string $email = null): string
    {
        $name = is_array($social) ? (string) ($social['name'] ?? '') : (string) $social->getName();
        $name = trim((string) preg_replace('/\s+/u', ' ', strip_tags($name)));

        if ($name === '' && $email !== null) {
            $name = Str::before($email, '@');
        }

        return Str::limit($name !== '' ? $name : __('account.social.default_name'), 100, '');
    }

    private function emailIsTrusted(SocialProvider $provider, SocialUser $social): bool
    {
        if ($provider !== SocialProvider::Google) {
            return false;
        }

        $raw = property_exists($social, 'user') && is_array($social->user) ? $social->user : [];

        return filter_var($raw['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }
}
