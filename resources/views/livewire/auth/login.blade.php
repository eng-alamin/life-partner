<div>
    <h1 class="text-2xl font-bold text-brand-800">{{ __('account.login.heading') }}</h1>
    <p class="mt-1 text-stone-600">{{ __('account.login.intro') }}</p>

    <form wire:submit="login" class="mt-6 space-y-4" novalidate>
        <x-field name="email" :label="__('account.fields.email')" :required="true">
            <x-input name="email" type="email" wire:model="email" autocomplete="username" inputmode="email" required />
        </x-field>

        <x-field name="password" :label="__('account.fields.password')" :required="true">
            <x-input name="password" type="password" wire:model="password" autocomplete="current-password" required />
        </x-field>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <label class="inline-flex min-h-11 items-center gap-2 text-stone-700">
                <input type="checkbox" wire:model="remember" class="size-5 rounded border-stone-500 text-brand-600">
                {{ __('account.login.remember') }}
            </label>
            <a href="{{ route('password.request') }}" wire:navigate class="font-medium text-brand-700 underline underline-offset-2">{{ __('account.login.forgot') }}</a>
        </div>

        <x-button type="submit" size="lg" class="w-full" wire:loading.attr="disabled" wire:target="login">
            {{ __('account.login.submit') }}
        </x-button>
    </form>

    <x-social-buttons />

    @if (app(\App\Services\Settings\SiteSettings::class)->registrationOpen())
        <p class="mt-6 text-center text-stone-600">
            {{ __('account.login.no_account') }}
            <a href="{{ route('register') }}" wire:navigate class="font-semibold text-brand-700 underline underline-offset-2">{{ __('nav.register') }}</a>
        </p>
    @endif
</div>
