<div>
    <h1 class="text-2xl font-bold text-brand-800">{{ __('account.verify.heading') }}</h1>
    <p class="mt-1 text-stone-600">{{ __('account.verify.intro', ['email' => $maskedEmail]) }}</p>

    @if ($resent)
        <x-alert type="success" class="mt-4">{{ __('account.verify.resent') }}</x-alert>
    @endif

    <form wire:submit="verify" class="mt-6 space-y-4" novalidate>
        <x-field name="code" :label="__('account.fields.code')" :hint="__('account.fields.code_hint', ['minutes' => \App\Services\Otp\OtpService::TTL_MINUTES])" :required="true">
            <x-input name="code" type="text" wire:model="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" class="text-center text-2xl tracking-[0.5em]" required />
        </x-field>

        <x-button type="submit" size="lg" class="w-full" wire:loading.attr="disabled" wire:target="verify">
            {{ __('account.verify.submit') }}
        </x-button>
    </form>

    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <x-button variant="ghost" wire:click="resend" wire:loading.attr="disabled" wire:target="resend">
            {{ __('account.verify.resend') }}
        </x-button>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-button type="submit" variant="ghost">{{ __('account.verify.wrong_email') }}</x-button>
        </form>
    </div>
</div>
