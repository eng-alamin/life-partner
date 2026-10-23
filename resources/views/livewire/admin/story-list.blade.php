<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-brand-800">{{ __('admin_stories.list.heading') }}</h1>
        <x-button :href="route('admin.stories.create')">{{ __('admin_stories.list.new') }}</x-button>
    </div>
    <p class="mt-1 text-stone-600">{{ __('admin_stories.list.intro') }}</p>

    <div class="mt-4 flex flex-wrap gap-2" role="group" aria-label="{{ __('admin_stories.list.heading') }}">
        <x-button type="button" size="sm" :variant="$tab === 'all' ? 'primary' : 'secondary'" wire:click="setTab('all')">{{ __('admin_stories.list.tab_all') }}</x-button>
        <x-button type="button" size="sm" :variant="$tab === 'draft' ? 'primary' : 'secondary'" wire:click="setTab('draft')">{{ __('admin_stories.list.tab_draft') }}</x-button>
        <x-button type="button" size="sm" :variant="$tab === 'published' ? 'primary' : 'secondary'" wire:click="setTab('published')">{{ __('admin_stories.list.tab_published') }}</x-button>
    </div>

    <div class="mt-4">
        @if ($rows === [])
            <x-empty-state :title="__('admin_stories.list.empty_title')" :text="__('admin_stories.list.empty_text')" />
        @else
            <ul class="space-y-3">
                @foreach ($rows as $row)
                    <li wire:key="story-{{ $row['code'] }}">
                        <x-card variant="small" padding="p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="min-w-0 space-y-1">
                                    <p class="text-lg font-semibold text-stone-900">{{ $row['title'] !== '' ? $row['title'] : $row['label'] }}</p>
                                    <p class="text-stone-700">{{ $row['label'] }}</p>
                                    <p class="flex flex-wrap items-center gap-2 text-sm text-stone-600">
                                        <x-badge :variant="$row['status']->badge()">{{ $row['status']->label() }}</x-badge>
                                        <x-badge :variant="$row['consent'] === 'given' ? 'brand' : ($row['consent'] === 'withdrawn' ? 'red' : 'gold')">{{ __('admin_stories.consent_state.'.$row['consent']) }}</x-badge>
                                        @if ($row['updated'])
                                            <span>{{ $row['updated'] }}</span>
                                        @endif
                                    </p>
                                </div>
                                <x-button :href="route('admin.stories.edit', $row['code'])" size="sm">{{ __('admin_stories.list.edit') }}</x-button>
                            </div>
                        </x-card>
                    </li>
                @endforeach
            </ul>

            <div class="mt-4 flex items-center justify-between gap-3">
                <x-button variant="secondary" size="sm" wire:click="previousPage" :disabled="$items->onFirstPage()">{{ __('admin_stories.list.previous') }}</x-button>
                <x-button variant="secondary" size="sm" wire:click="nextPage" :disabled="! $items->hasMorePages()">{{ __('admin_stories.list.next') }}</x-button>
            </div>
        @endif
    </div>
</div>
