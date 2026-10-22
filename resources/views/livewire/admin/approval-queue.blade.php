<div>
    <h1 class="text-2xl font-bold text-brand-800">{{ __('admin.queue.heading') }}</h1>
    <p class="mt-1 text-stone-600">{{ __('admin.queue.intro') }}</p>

    <div class="mt-4">
        @if ($items->isEmpty())
            <x-empty-state :title="__('admin.queue.empty_title')" :text="__('admin.queue.empty_text')" />
        @else
            <ul class="space-y-3">
                @foreach ($items as $item)
                    <li wire:key="biodata-{{ $item->id }}">
                        <x-card variant="small" padding="p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="min-w-0 space-y-1">
                                    <p class="font-mono text-lg font-semibold text-stone-900">{{ $item->public_code }}</p>
                                    <p class="text-stone-700">
                                        {{ $item->type?->label() }}
                                        @if ($item->age !== null)
                                            · {{ __('admin.queue.age', ['age' => $item->age]) }}
                                        @endif
                                        · {{ \App\Support\Countries::name($item->present_country) }}
                                        @if ($item->presentDistrict)
                                            , {{ $item->presentDistrict->name() }}
                                        @endif
                                    </p>
                                    <p class="text-sm text-stone-600">
                                        {{ __('admin.queue.submitted', ['time' => $item->submitted_at?->diffForHumans() ?? '—']) }}
                                    </p>
                                </div>
                                <x-button :href="route('admin.approvals.show', $item->public_code)" size="sm">
                                    {{ __('admin.queue.review') }}
                                </x-button>
                            </div>
                        </x-card>
                    </li>
                @endforeach
            </ul>

            <div class="mt-4 flex items-center justify-between gap-3">
                <x-button variant="secondary" size="sm" wire:click="previousPage" :disabled="$items->onFirstPage()">
                    {{ __('admin.queue.previous') }}
                </x-button>
                <x-button variant="secondary" size="sm" wire:click="nextPage" :disabled="! $items->hasMorePages()">
                    {{ __('admin.queue.next') }}
                </x-button>
            </div>
        @endif
    </div>
</div>
