<div>
    <h1 class="text-2xl font-bold text-brand-800">{{ __('blocks.ui.heading') }}</h1>
    <p class="mt-1 text-stone-700">{{ __('blocks.ui.intro') }}</p>

    @if ($notice !== '')
        <x-alert type="success" class="mt-4">{{ $notice }}</x-alert>
    @endif

    @error('form')
        <x-alert type="danger" class="mt-4">{{ $message }}</x-alert>
    @enderror

    @if ($rows === [])
        <x-empty-state class="mt-4" :title="__('blocks.ui.empty_title')" :text="__('blocks.ui.empty_text')" />
    @else
        <ul class="mt-4 space-y-3">
            @foreach ($rows as $row)
                <li wire:key="block-{{ $row['code'] }}">
                    <x-card variant="small" padding="p-4 sm:p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="font-mono text-lg font-semibold text-stone-900">{{ $row['code'] }}</p>
                                <p class="text-sm text-stone-600">{{ __('blocks.ui.blocked_on', ['date' => $row['blocked_on']]) }}</p>
                            </div>
                            <x-button type="button" variant="secondary" size="sm" wire:click="unblock('{{ $row['code'] }}')" wire:loading.attr="disabled">{{ __('blocks.ui.unblock') }}</x-button>
                        </div>
                    </x-card>
                </li>
            @endforeach
        </ul>
    @endif
</div>
