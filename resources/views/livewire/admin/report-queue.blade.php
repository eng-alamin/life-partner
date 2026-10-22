<div>
    <h1 class="text-2xl font-bold text-brand-800">{{ __('admin_reports.queue.heading') }}</h1>
    <p class="mt-1 text-stone-600">{{ __('admin_reports.queue.intro') }}</p>

    <div class="mt-4 flex gap-2" role="group" aria-label="{{ __('admin_reports.queue.heading') }}">
        <x-button type="button" size="sm" :variant="$tab === 'open' ? 'primary' : 'secondary'" wire:click="setTab('open')">{{ __('admin_reports.queue.tab_open') }}</x-button>
        <x-button type="button" size="sm" :variant="$tab === 'closed' ? 'primary' : 'secondary'" wire:click="setTab('closed')">{{ __('admin_reports.queue.tab_closed') }}</x-button>
    </div>

    <div class="mt-4">
        @if ($items->isEmpty())
            <x-empty-state
                :title="$tab === 'open' ? __('admin_reports.queue.empty_open_title') : __('admin_reports.queue.empty_closed_title')"
                :text="$tab === 'open' ? __('admin_reports.queue.empty_open_text') : __('admin_reports.queue.empty_closed_text')"
            />
        @else
            <ul class="space-y-3">
                @foreach ($items as $item)
                    <li wire:key="report-{{ $item->id }}">
                        <x-card variant="small" padding="p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="min-w-0 space-y-1">
                                    <p class="font-mono text-lg font-semibold text-stone-900">{{ $item->reported_code }}</p>
                                    <p class="text-stone-700">{{ $item->reason->label() }}</p>
                                    <p class="flex flex-wrap items-center gap-2 text-sm text-stone-600">
                                        <x-badge :variant="$item->status->badge()">{{ $item->status->label() }}</x-badge>
                                        <span>{{ __('admin_reports.queue.sent', ['time' => $item->created_at?->diffForHumans() ?? '—']) }}</span>
                                    </p>
                                </div>
                                <x-button :href="route('admin.reports.show', $item->code)" size="sm">
                                    {{ __('admin_reports.queue.open_button') }}
                                </x-button>
                            </div>
                        </x-card>
                    </li>
                @endforeach
            </ul>

            <div class="mt-4 flex items-center justify-between gap-3">
                <x-button variant="secondary" size="sm" wire:click="previousPage" :disabled="$items->onFirstPage()">
                    {{ __('admin_reports.queue.previous') }}
                </x-button>
                <x-button variant="secondary" size="sm" wire:click="nextPage" :disabled="! $items->hasMorePages()">
                    {{ __('admin_reports.queue.next') }}
                </x-button>
            </div>
        @endif
    </div>
</div>
