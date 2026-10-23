<x-card>
    <h2 class="text-xl font-semibold text-brand-900">{{ __('contact.heading') }}</h2>
    <p class="mt-2 text-stone-700">{{ __('contact.intro') }}</p>

    @if ($sent)
        <x-alert type="success" class="mt-4">{{ __('contact.sent') }}</x-alert>
    @else
        @error('form')
            <x-alert type="danger" class="mt-4">{{ $message }}</x-alert>
        @enderror

        <form wire:submit="send" class="mt-4 space-y-4" novalidate>
            {{-- Honeypot: hidden from people and screen readers, bots fill it. --}}
            <div class="absolute -left-[9999px] h-0 w-0 overflow-hidden" aria-hidden="true">
                <label for="f-website">{{ __('contact.honeypot') }}</label>
                <input type="text" id="f-website" name="website" wire:model="website" tabindex="-1" autocomplete="off">
            </div>

            <x-field name="name" :label="__('contact.fields.name')" required>
                <x-input name="name" wire:model="name" autocomplete="name" maxlength="100" />
            </x-field>

            <x-field name="email" :label="__('contact.fields.email')" required>
                <x-input name="email" type="email" wire:model="email" autocomplete="email" maxlength="255" />
            </x-field>

            <x-field name="phone" :label="__('contact.fields.phone')" :hint="__('contact.hints.phone')">
                <x-input name="phone" type="tel" wire:model="phone" autocomplete="tel" maxlength="20" />
            </x-field>

            <x-field name="subject" :label="__('contact.fields.subject')" required>
                <x-input name="subject" wire:model="subject" maxlength="150" />
            </x-field>

            <x-field name="message" :label="__('contact.fields.message')" required>
                <x-textarea name="message" rows="6" wire:model="message" maxlength="{{ \App\Actions\Contact\SubmitContactMessage::MESSAGE_MAX }}" />
            </x-field>

            <p class="text-sm text-stone-600">
                {{ __('contact.privacy_note', ['days' => \App\Support\Digits::localize(\App\Models\ContactMessage::RETENTION_DAYS)]) }}
            </p>

            <x-button type="submit" wire:loading.attr="disabled" wire:target="send">{{ __('contact.send') }}</x-button>
        </form>
    @endif
</x-card>
