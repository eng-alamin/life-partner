<div>
    <h1 class="text-2xl font-bold text-brand-800">{{ __('account.register.heading') }}</h1>

    @if (! $open)
        {{-- Registration is switched off in Admin > Settings. The server refuses the sign-up as well. --}}
        <x-alert type="warning" class="mt-4">
            <p class="font-semibold">{{ __('site.registration.closed_title') }}</p>
            <p class="mt-1">{{ __('site.registration.closed_text') }}</p>
        </x-alert>

        <x-button :href="route('login')" size="lg" class="mt-6 w-full">{{ __('nav.login') }}</x-button>
    @else
        <p class="mt-1 text-stone-600">{{ __('account.register.intro') }}</p>

        <form wire:submit="register" class="mt-6 space-y-4" novalidate>
            <x-field name="name" :label="__('account.fields.name')" :required="true">
                <x-input name="name" type="text" wire:model="name" autocomplete="name" required />
            </x-field>

            <x-field name="email" :label="__('account.fields.email')" :required="true">
                <x-input name="email" type="email" wire:model="email" autocomplete="email" inputmode="email" required />
            </x-field>

            <x-field name="password" :label="__('account.fields.password')" :hint="__('account.fields.password_hint')" :required="true">
                <x-input name="password" type="password" wire:model="password" autocomplete="new-password" required />
            </x-field>

            <x-field name="password_confirmation" :label="__('account.fields.password_confirmation')" :required="true">
                <x-input name="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password" required />
            </x-field>

            <x-button type="submit" size="lg" class="w-full" wire:loading.attr="disabled" wire:target="register">
                {{ __('account.register.submit') }}
            </x-button>
        </form>

        <x-social-buttons />

        <p class="mt-6 text-center text-stone-600">
            {{ __('account.register.have_account') }}
            <a href="{{ route('login') }}" wire:navigate class="font-semibold text-brand-700 underline underline-offset-2">{{ __('nav.login') }}</a>
        </p>
    @endif
</div>
