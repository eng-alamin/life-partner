<div class="mt-4">
    @error('form')
        <x-alert type="danger" class="mb-2">{{ $message }}</x-alert>
    @enderror

    <x-button
        type="button"
        variant="ghost"
        size="sm"
        wire:click="block"
        wire:confirm="{{ __('blocks.ui.confirm_block') }}"
        wire:loading.attr="disabled"
    >{{ __('blocks.ui.block_button') }}</x-button>
    <p class="mt-1 text-sm text-stone-600">{{ __('blocks.ui.block_help') }}</p>
</div>
