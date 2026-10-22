<div>
    <h1 class="text-2xl font-bold text-brand-800">{{ __('notifications.settings.heading') }}</h1>
    <p class="mt-1 text-stone-700">{{ __('notifications.settings.intro') }}</p>

    {{-- ===== Mobile number (for SMS) ===== --}}
    <x-card class="mt-4">
        <h2 class="text-lg font-bold text-brand-800">{{ __('sms.settings.phone_heading') }}</h2>
        <p class="mt-1 text-stone-700">{{ __('sms.settings.phone_intro') }}</p>

        @if ($phoneNotice !== '')
            <x-alert type="success" class="mt-3">{{ $phoneNotice }}</x-alert>
        @endif

        @unless ($smsReady)
            <x-alert type="warning" class="mt-3">{{ __('sms.settings.not_available') }}</x-alert>
        @endunless

        @if ($phoneVerified)
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                <p class="font-semibold text-stone-900">
                    <span dir="ltr">{{ $maskedPhone }}</span>
                    <x-badge variant="brand">{{ __('sms.settings.verified') }}</x-badge>
                </p>
                <x-button type="button" variant="secondary" wire:click="removePhone" wire:confirm="{{ __('sms.settings.remove_confirm') }}" wire:loading.attr="disabled" wire:target="removePhone">
                    {{ __('sms.settings.remove') }}
                </x-button>
            </div>
        @elseif ($phoneWaiting)
            <p class="mt-4 text-stone-800">{{ __('sms.settings.code_intro', ['number' => $maskedPhone]) }}</p>

            <form wire:submit="confirmCode" class="mt-3 space-y-4" novalidate>
                <x-field name="code" :label="__('account.fields.code')" :hint="__('sms.settings.code_hint', ['minutes' => \App\Services\Otp\OtpService::TTL_MINUTES])" :required="true">
                    <x-input name="code" type="text" wire:model="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" class="text-center text-2xl tracking-[0.5em]" required />
                </x-field>

                <div class="flex flex-wrap gap-2">
                    <x-button type="submit" wire:loading.attr="disabled" wire:target="confirmCode">{{ __('sms.settings.verify') }}</x-button>
                    <x-button type="button" variant="secondary" wire:click="resendCode" wire:loading.attr="disabled" wire:target="resendCode">{{ __('sms.settings.resend') }}</x-button>
                    <x-button type="button" variant="ghost" wire:click="removePhone" wire:loading.attr="disabled" wire:target="removePhone">{{ __('sms.settings.change_number') }}</x-button>
                </div>
            </form>
            @error('phone')
                <p class="mt-2 text-sm text-red-700" role="alert">{{ $message }}</p>
            @enderror
        @else
            <form wire:submit="sendCode" class="mt-4 space-y-4" novalidate>
                <x-field name="phone" :label="__('sms.settings.phone_label')" :hint="__('sms.settings.phone_hint')" :required="true">
                    <x-input name="phone" type="tel" wire:model="phone" inputmode="tel" autocomplete="tel" maxlength="25" dir="ltr" required />
                </x-field>

                <x-button type="submit" wire:loading.attr="disabled" wire:target="sendCode">{{ __('sms.settings.send_code') }}</x-button>
            </form>
        @endif
    </x-card>

    {{-- ===== Which messages ===== --}}
    @if ($notice !== '')
        <x-alert type="success" class="mt-4">{{ $notice }}</x-alert>
    @endif

    <x-card class="mt-4">
        <form wire:submit="save" class="space-y-5">
            @foreach ($categories as $category)
                <div class="space-y-2" wire:key="category-{{ $category->value }}">
                    <div class="flex items-start gap-3">
                        @if ($category->isMandatory())
                            <input type="checkbox" id="email-{{ $category->value }}" checked disabled class="mt-1 size-6 shrink-0 rounded border-stone-400 text-brand-700">
                        @else
                            <input type="checkbox" id="email-{{ $category->value }}" wire:model="email.{{ $category->value }}" class="mt-1 size-6 shrink-0 rounded border-stone-400 text-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
                        @endif
                        <label for="email-{{ $category->value }}" class="min-w-0">
                            <span class="block font-semibold text-stone-900">{{ $category->label() }}</span>
                            <span class="block text-stone-700">{{ $category->hint() }}</span>
                            @if ($category->isMandatory())
                                <span class="block text-sm text-stone-600">{{ __('notifications.settings.mandatory') }}</span>
                            @endif
                        </label>
                    </div>

                    @if ($category->sendsSms())
                        <div class="flex items-start gap-3 ps-9">
                            @if ($category->isMandatory())
                                <input type="checkbox" id="sms-{{ $category->value }}" checked disabled class="mt-1 size-6 shrink-0 rounded border-stone-400 text-brand-700">
                            @else
                                <input type="checkbox" id="sms-{{ $category->value }}" wire:model="sms.{{ $category->value }}" @disabled(! $phoneVerified) class="mt-1 size-6 shrink-0 rounded border-stone-400 text-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
                            @endif
                            <label for="sms-{{ $category->value }}" class="min-w-0">
                                <span class="block font-medium text-stone-900">{{ __('sms.settings.sms_toggle') }}</span>
                                <span class="block text-sm text-stone-600">
                                    {{ $phoneVerified ? __('sms.settings.sms_hint_'.$category->value) : __('sms.settings.sms_needs_number') }}
                                </span>
                            </label>
                        </div>
                    @endif
                </div>
            @endforeach

            <p class="text-sm text-stone-600">{{ __('notifications.settings.in_app_note') }}</p>
            <p class="text-sm text-stone-600">{{ __('sms.settings.privacy_note') }}</p>

            <div class="flex flex-wrap gap-2">
                <x-button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('notifications.settings.save') }}</x-button>
                @if (Route::has('notifications.index'))
                    <x-button :href="route('notifications.index')" variant="secondary">{{ __('notifications.settings.back') }}</x-button>
                @endif
            </div>
        </form>
    </x-card>
</div>
