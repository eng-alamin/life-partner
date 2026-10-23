<div>
    <h1 class="text-2xl font-bold text-brand-800">{{ __('admin_settings.heading') }}</h1>
    <p class="mt-1 text-stone-600">{{ __('admin_settings.intro') }}</p>

    @error('form')
        <x-alert type="danger" class="mt-4">{{ $message }}</x-alert>
    @enderror

    <form wire:submit="save" class="mt-4 space-y-4" novalidate>
        <x-card class="space-y-3">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-lg font-semibold text-stone-900">{{ __('admin_settings.registration.heading') }}</h2>
                <x-badge :variant="$registrationIsOpen ? 'brand' : 'gold'">{{ $registrationIsOpen ? __('admin_settings.registration.status_open') : __('admin_settings.registration.status_closed') }}</x-badge>
            </div>

            <label class="flex items-start gap-3 text-stone-800">
                <input type="checkbox" wire:model="registration_open" class="mt-1 size-5 accent-brand-600">
                <span>{{ __('admin_settings.registration.label') }}</span>
            </label>
            <p class="text-sm text-stone-600">{{ __('admin_settings.registration.hint') }}</p>

            @error('registration_open')
                <p class="text-sm font-medium text-red-700" role="alert">{{ $message }}</p>
            @enderror
        </x-card>

        <x-card class="space-y-3">
            <h2 class="text-lg font-semibold text-stone-900">{{ __('admin_settings.guardian.heading') }}</h2>

            <label class="flex items-start gap-3 text-stone-800">
                <input type="checkbox" wire:model="guardian_approval" class="mt-1 size-5 accent-brand-600">
                <span>{{ __('admin_settings.guardian.label') }}</span>
            </label>
            <p class="text-sm text-stone-600">{{ __('admin_settings.guardian.hint') }}</p>

            @error('guardian_approval')
                <p class="text-sm font-medium text-red-700" role="alert">{{ $message }}</p>
            @enderror
        </x-card>

        <x-card class="space-y-4">
            <div>
                <h2 class="text-lg font-semibold text-stone-900">{{ __('admin_settings.contact.heading') }}</h2>
                <p class="mt-1 text-sm text-stone-600">{{ __('admin_settings.contact.intro') }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="contact_email" :label="__('admin_settings.contact.email')">
                    <x-input name="contact_email" type="email" wire:model="contact_email" maxlength="120" autocomplete="off" inputmode="email" dir="ltr" />
                </x-field>

                <x-field name="contact_phone" :label="__('admin_settings.contact.phone')" :hint="__('admin_settings.contact.phone_hint')">
                    <x-input name="contact_phone" type="tel" wire:model="contact_phone" maxlength="30" autocomplete="off" inputmode="tel" dir="ltr" />
                </x-field>
            </div>

            <x-field name="contact_address_bn" :label="__('admin_settings.contact.address_bn')" :hint="__('admin_settings.contact.address_hint', ['max' => \App\Support\Digits::localize($addressMax)])">
                <x-input name="contact_address_bn" wire:model="contact_address_bn" maxlength="{{ $addressMax }}" />
            </x-field>

            <x-field name="contact_address_en" :label="__('admin_settings.contact.address_en')">
                <x-input name="contact_address_en" wire:model="contact_address_en" maxlength="{{ $addressMax }}" dir="ltr" />
            </x-field>
        </x-card>

        <x-button type="submit" wire:loading.attr="disabled">{{ __('admin_settings.save') }}</x-button>
    </form>
</div>
