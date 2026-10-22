<div>
    <h1 class="text-2xl font-bold text-brand-800">{{ __('account.reset.heading') }}</h1>

    @if ($step === 'request')
        <p class="mt-1 text-stone-600">{{ __('account.reset.intro_request') }}</p>

        <form wire:submit="sendCode" class="mt-6 space-y-4" novalidate>
            <x-field name="email" :label="__('account.fields.email')" :required="true">
                <x-input name="email" type="email" wire:model="email" autocomplete="email" inputmode="email" required />
            </x-field>

            <x-button type="submit" size="lg" class="w-full" wire:loading.attr="disabled" wire:target="sendCode">
                {{ __('account.reset.send_code') }}
            </x-button>
        </form>
    @else
        <x-alert type="info" class="mt-4">{{ __('account.reset.sent_notice') }}</x-alert>

        <form wire:submit="resetPassword" class="mt-6 space-y-4" novalidate>
            <x-field name="email" :label="__('account.fields.email')" :required="true">
                <x-input name="email" type="email" wire:model="email" autocomplete="email" inputmode="email" required />
            </x-field>

            <x-field name="code" :label="__('account.fields.code')" :hint="__('account.fields.code_hint', ['minutes' => \App\Services\Otp\OtpService::TTL_MINUTES])" :required="true">
                <x-input name="code" type="text" wire:model="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" class="text-center text-2xl tracking-[0.5em]" required />
            </x-field>

            <x-field name="password" :label="__('account.reset.new_password')" :hint="__('account.fields.password_hint')" :required="true">
                <x-input name="password" type="password" wire:model="password" autocomplete="new-password" required />
            </x-field>

            <x-field name="password_confirmation" :label="__('account.fields.password_confirmation')" :required="true">
                <x-input name="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password" required />
            </x-field>

            <x-button type="submit" size="lg" class="w-full" wire:loading.attr="disabled" wire:target="resetPassword">
                {{ __('account.reset.submit') }}
            </x-button>
        </form>

        <div class="mt-4">
            <x-button variant="ghost" wire:click="backToRequest">{{ __('account.reset.resend') }}</x-button>
        </div>
    @endif

    <p class="mt-6 text-center text-stone-600">
        <a href="{{ route('login') }}" wire:navigate class="font-semibold text-brand-700 underline underline-offset-2">{{ __('account.reset.back_to_login') }}</a>
    </p>
</div>
