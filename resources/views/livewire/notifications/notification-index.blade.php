<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-brand-800">{{ __('notifications.page.heading') }}</h1>
            <p class="mt-1 text-stone-700">{{ __('notifications.page.intro') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($unread > 0)
                <x-button type="button" variant="secondary" size="sm" wire:click="markAllRead" wire:loading.attr="disabled">{{ __('notifications.page.mark_all_read') }}</x-button>
            @endif
            @if (Route::has('notifications.settings'))
                <x-button :href="route('notifications.settings')" variant="ghost" size="sm">{{ __('notifications.page.settings_link') }}</x-button>
            @endif
        </div>
    </div>

    <div class="mt-4 flex gap-2" role="group" aria-label="{{ __('notifications.page.heading') }}">
        <x-button type="button" size="sm" :variant="$filter === 'all' ? 'primary' : 'secondary'" wire:click="setFilter('all')">{{ __('notifications.page.filter_all') }}</x-button>
        <x-button type="button" size="sm" :variant="$filter === 'unread' ? 'primary' : 'secondary'" wire:click="setFilter('unread')">
            {{ __('notifications.page.filter_unread') }}@if ($unread > 0) ({{ \App\Support\Digits::localize($unread) }})@endif
        </x-button>
    </div>

    <div class="mt-4">
        @if ($rows === [])
            <x-empty-state
                :title="$filter === 'unread' ? __('notifications.page.empty_unread_title') : __('notifications.page.empty_title')"
                :text="$filter === 'unread' ? __('notifications.page.empty_unread_text') : __('notifications.page.empty_text')"
            />
        @else
            <ul class="space-y-3">
                @foreach ($rows as $row)
                    <li wire:key="notification-{{ $row['id'] }}">
                        <x-card variant="small" padding="p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0 flex-1 space-y-1">
                                    <p class="flex flex-wrap items-center gap-2 text-lg font-semibold text-stone-900">
                                        {{ $row['title'] }}
                                        @unless ($row['read'])
                                            <x-badge variant="brand">{{ __('notifications.page.new') }}</x-badge>
                                        @endunless
                                    </p>
                                    <p class="text-stone-700">{{ $row['body'] }}</p>
                                    <p class="text-sm text-stone-600">{{ $row['when'] }}</p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <x-button type="button" size="sm" wire:click="open('{{ $row['id'] }}')" wire:loading.attr="disabled">{{ __('notifications.page.open') }}</x-button>
                                    @unless ($row['read'])
                                        <x-button type="button" variant="ghost" size="sm" wire:click="markRead('{{ $row['id'] }}')" wire:loading.attr="disabled">{{ __('notifications.page.mark_read') }}</x-button>
                                    @endunless
                                </div>
                            </div>
                        </x-card>
                    </li>
                @endforeach
            </ul>

            <div class="mt-4 flex items-center justify-between gap-3">
                <x-button variant="secondary" size="sm" wire:click="previousPage" :disabled="$items->onFirstPage()">{{ __('notifications.page.previous') }}</x-button>
                <x-button variant="secondary" size="sm" wire:click="nextPage" :disabled="! $items->hasMorePages()">{{ __('notifications.page.next') }}</x-button>
            </div>
        @endif
    </div>
</div>
