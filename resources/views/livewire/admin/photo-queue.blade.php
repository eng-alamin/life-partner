<div>
    <h1 class="text-2xl font-bold text-brand-800">{{ __('photos.admin.heading') }}</h1>
    <p class="mt-1 text-stone-600">{{ __('photos.admin.intro') }}</p>

    @if ($notice !== '')
        <x-alert type="success" class="mt-4">{{ $notice }}</x-alert>
    @endif

    @error('form')
        <x-alert type="danger" class="mt-4">{{ $message }}</x-alert>
    @enderror

    <div class="mt-4">
        @if ($items === [])
            <x-empty-state :title="__('photos.admin.empty_title')" :text="__('photos.admin.empty_text')" />
        @else
            <ul class="grid gap-4 lg:grid-cols-2">
                @foreach ($items as $item)
                    <li wire:key="photo-{{ $item['id'] }}">
                        <x-card variant="small" padding="p-4">
                            <p class="font-mono text-lg font-semibold text-stone-900">{{ $item['biodata_code'] }}</p>
                            <p class="text-sm text-stone-600">{{ $item['type'] }} · {{ $item['uploaded'] }}</p>

                            <img src="{{ $item['url'] }}" alt="{{ __('photos.admin.alt') }}" loading="lazy" class="mt-3 max-h-96 w-auto max-w-full rounded-xl">

                            <div class="mt-3 space-y-3">
                                <x-button wire:click="approve({{ $item['id'] }})" wire:loading.attr="disabled" size="sm">{{ __('photos.admin.approve') }}</x-button>

                                <div class="space-y-1.5">
                                    <label for="reason-{{ $item['id'] }}" class="block text-sm font-medium text-stone-700">{{ __('photos.admin.reason_label') }}</label>
                                    <input
                                        type="text"
                                        id="reason-{{ $item['id'] }}"
                                        maxlength="200"
                                        wire:model="reasons.{{ $item['id'] }}"
                                        class="block min-h-11 w-full rounded-xl border border-stone-500 bg-white/70 px-3 py-2 text-stone-900"
                                    >
                                    @error('reasons.'.$item['id'])
                                        <p class="text-sm font-medium text-red-700" role="alert">{{ $message }}</p>
                                    @enderror
                                    <x-button variant="danger" size="sm" wire:click="reject({{ $item['id'] }})" wire:loading.attr="disabled">{{ __('photos.admin.reject') }}</x-button>
                                </div>
                            </div>
                        </x-card>
                    </li>
                @endforeach
            </ul>

            <div class="mt-4 flex items-center justify-between gap-3">
                <x-button variant="secondary" size="sm" wire:click="previousPage" :disabled="$paginator->onFirstPage()">{{ __('admin.queue.previous') }}</x-button>
                <x-button variant="secondary" size="sm" wire:click="nextPage" :disabled="! $paginator->hasMorePages()">{{ __('admin.queue.next') }}</x-button>
            </div>
        @endif
    </div>
</div>
