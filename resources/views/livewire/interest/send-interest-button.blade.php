<div>
    @if ($state !== null && $state !== 'own')
        <x-card class="mt-4">
            <h2 class="text-lg font-semibold text-stone-900">{{ __('interests.ui.heading') }}</h2>

            @if ($notice !== '')
                <x-alert type="success" class="mt-3">{{ $notice }}</x-alert>
            @endif

            @if ($state === 'can_send')
                <p class="mt-1 text-stone-700">{{ __('interests.ui.intro') }}</p>

                <form wire:submit="send" class="mt-3 space-y-3">
                    @error('form')
                        <x-alert type="danger">{{ $message }}</x-alert>
                    @enderror

                    <x-field name="message" :label="__('interests.ui.message_label')" :hint="__('interests.ui.message_hint', ['max' => \App\Support\Digits::localize($max)])">
                        <x-textarea name="message" rows="3" maxlength="{{ $max }}" wire:model="message" />
                    </x-field>

                    <x-button type="submit" wire:loading.attr="disabled">{{ __('interests.ui.send') }}</x-button>
                </form>
            @elseif ($state === 'sent')
                <p class="mt-1 text-stone-700">{{ __('interests.ui.state_sent') }}</p>
                <x-button :href="route('interests.index', ['tab' => 'sent'])" variant="secondary" size="sm" class="mt-3">{{ __('interests.ui.open_list') }}</x-button>
            @elseif ($state === 'received')
                <p class="mt-1 text-stone-700">{{ __('interests.ui.state_received') }}</p>
                <x-button :href="route('interests.index')" size="sm" class="mt-3">{{ __('interests.ui.open_list') }}</x-button>
            @elseif ($state === 'connected')
                <p class="mt-1 text-stone-700">{{ __('interests.ui.state_connected') }}</p>
                <x-button :href="route('interests.index')" variant="secondary" size="sm" class="mt-3">{{ __('interests.ui.open_list') }}</x-button>
            @elseif ($state === 'no_biodata')
                <p class="mt-1 text-stone-700">{{ __('interests.ui.state_no_biodata') }}</p>
                <x-button :href="route('my-biodata')" size="sm" class="mt-3">{{ __('interests.ui.create_biodata') }}</x-button>
            @else
                <p class="mt-1 text-stone-700">{{ __('interests.ui.state_not_approved') }}</p>
                <x-button :href="route('my-biodata')" variant="secondary" size="sm" class="mt-3">{{ __('interests.ui.go_to_biodata') }}</x-button>
            @endif
        </x-card>
    @endif
</div>
