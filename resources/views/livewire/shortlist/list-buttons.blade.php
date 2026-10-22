<div class="mt-4">
    @error('form')
        <x-alert type="danger" class="mb-2">{{ $message }}</x-alert>
    @enderror

    @if ($notice !== '')
        <x-alert type="success" class="mb-2">{{ $notice }}</x-alert>
    @endif

    <div class="flex flex-wrap gap-2">
        <x-button type="button" :variant="$shortlisted ? 'secondary' : 'primary'" size="sm" wire:click="toggleShortlist" wire:loading.attr="disabled">
            {{ $shortlisted ? __('lists.ui.remove_shortlist') : __('lists.ui.add_shortlist') }}
        </x-button>

        <x-button type="button" variant="ghost" size="sm" wire:click="toggleIgnore" wire:loading.attr="disabled">
            {{ $ignored ? __('lists.ui.restore') : __('lists.ui.ignore') }}
        </x-button>
    </div>
    <p class="mt-1 text-sm text-stone-600">{{ __('lists.ui.ignore_help') }}</p>
</div>
