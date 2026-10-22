<div>
    <h1 class="text-2xl font-bold text-brand-800">{{ __('account.social.complete_heading') }}</h1>

    @if ($step === 'email')
        <p class="mt-1 text-stone-600">{{ __('account.social.complete_intro', ['provider' => $providerName]) }}</p>

        <form wire:submit="sendCode" class="mt-6 space-y-4" novalidate>
            <x-field name="email" :label="__('account.fields.email')" :required="true">
                <x-input name="email" type="email" wire:model="email" autocomplete="email" inputmode="email" required />
            </x-field>

            <x-button type="submit" size="lg" class="w-full" wire:loading.attr="disabled" wire:target="sendCode">
                {{ __('account.social.send_code') }}
            </x-button>
        </form>
    @else
        <x-alert type="info" class="mt-4">{{ __('account.social.code_sent') }}</x-alert>

        <form wire:submit="verify" class="mt-6 space-y-4" novalidate>
            <x-field name="code" :label="__('account.fields.code')" :hint="__('account.fields.code_hint', ['minutes' => \App\Services\Otp\OtpService::TTL_MINUTES])" :required="true">
                <x-input name="code" type="text" wire:model="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" class="text-center text-2xl tracking-[0.5em]" required />
            </x-field>

            <x-button type="submit" size="lg" class="w-full" wire:loading.attr="disabled" wire:target="verify">
                {{ __('account.social.submit') }}
            </x-button>
        </form>
    @endif

    <div class="mt-4">
        <x-button variant="ghost" wire:click="cancel">{{ __('account.social.cancel') }}</x-button>
    </div>
</div>
