<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Enums\OtpPurpose;
use App\Enums\OtpResult;
use App\Enums\SocialProvider;
use App\Exceptions\OtpThrottledException;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Auth\LoginFinisher;
use App\Services\Auth\SocialAuthService;
use App\Services\Otp\OtpService;
use App\Services\Settings\SiteSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Finishes a social sign-in whose email Google/Facebook did not vouch for.
 * The person must prove they own the address with an emailed code before the
 * social account is linked. The pending provider identity lives in the server
 * session only; the browser never sends it back.
 */
#[Layout('layouts::auth')]
class CompleteSocialSignup extends Component
{
    public string $step = 'email';

    public string $email = '';

    public string $code = '';

    public function mount(): void
    {
        $pending = session('social_pending');

        if (! is_array($pending)) {
            $this->redirectRoute('login');

            return;
        }

        $this->email = (string) ($pending['email'] ?? '');
    }

    public function sendCode(OtpService $otp, SocialAuthService $social, SiteSettings $settings): void
    {
        $pending = $this->pending();
        $this->email = Str::lower(trim($this->email));

        $this->validate(['email' => ['required', 'string', 'email:rfc', 'max:255']]);

        $key = 'social-code:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->addError('email', __('account.throttled_minutes', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)]));

            return;
        }

        RateLimiter::hit($key, 900);

        $user = User::query()->where('email', $this->email)->first();

        if ($user === null) {
            // Nobody new may start an account while registration is closed.
            if (! $settings->registrationOpen()) {
                $this->addError('email', __('site.registration.closed_short'));

                return;
            }

            // Password-less, unverified shell; it becomes usable only after the code is confirmed.
            $user = User::query()->create([
                'name' => $social->displayName($pending, $this->email),
                'email' => $this->email,
                'password' => null,
                'locale' => app()->getLocale(),
            ])->refresh(); // load DB defaults such as status = active
        }

        if ($user->isActive()) {
            try {
                $otp->issue($user, OtpPurpose::Link);
            } catch (OtpThrottledException) {
                // Same screen either way.
            }
        }

        session()->put('social_pending.proof_email', $this->email);

        $this->resetErrorBag();
        $this->step = 'code';
    }

    public function verify(OtpService $otp, LoginFinisher $finisher): void
    {
        $pending = $this->pending();
        $proofEmail = (string) ($pending['proof_email'] ?? '');

        $this->validate(['code' => ['required', 'string', 'max:12']]);

        $key = 'social-verify:'.$proofEmail.'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->addError('code', __('account.throttled_minutes', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)]));

            return;
        }

        $user = $proofEmail !== '' ? User::query()->where('email', $proofEmail)->first() : null;

        $result = ($user !== null && $user->isActive())
            ? $otp->verify($user, OtpPurpose::Link, $this->code)
            : OtpResult::Invalid;

        if ($result !== OtpResult::Verified) {
            RateLimiter::hit($key, 900);
            $this->addError('code', __('account.otp.'.Str::snake($result->name)));
            $this->code = '';

            return;
        }

        $provider = SocialProvider::tryFrom((string) ($pending['provider'] ?? ''));

        if ($provider === null) {
            $this->redirectRoute('login');

            return;
        }

        $linkedElsewhere = false;

        DB::transaction(function () use ($user, $provider, $pending, &$linkedElsewhere): void {
            $user->markEmailAsVerified();

            $account = SocialAccount::query()->firstOrCreate(
                ['provider' => $provider->value, 'provider_user_id' => (string) $pending['provider_user_id']],
                ['user_id' => $user->getKey()],
            );

            $linkedElsewhere = $account->user_id !== $user->getKey();
        });

        if ($linkedElsewhere) {
            session()->forget('social_pending');
            session()->flash('error', __('account.social.cancelled'));
            $this->redirectRoute('login');

            return;
        }

        RateLimiter::clear($key);
        session()->forget('social_pending');

        Auth::login($user);
        $finisher->finish($user);

        $this->redirectIntended(route('dashboard'));
    }

    public function cancel(): void
    {
        session()->forget('social_pending');

        $this->redirectRoute('login');
    }

    /** @return array<string, mixed> */
    private function pending(): array
    {
        $pending = session('social_pending');

        abort_unless(is_array($pending), 403);

        return $pending;
    }

    public function render()
    {
        $provider = SocialProvider::tryFrom((string) session('social_pending.provider'));

        return view('livewire.auth.complete-social-signup', ['providerName' => $provider?->label() ?? ''])
            ->title(__('account.social.complete_title'));
    }
}
