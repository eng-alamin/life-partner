<div>
    <h1 class="text-2xl font-bold text-brand-800">{{ __('admin_contact.heading') }}</h1>
    <p class="mt-1 text-stone-600">{{ __('admin_contact.intro') }}</p>

    <div class="mt-4 flex flex-wrap gap-2" role="group" aria-label="{{ __('admin_contact.heading') }}">
        @foreach (['new', 'handled', 'spam'] as $name)
            <x-button type="button" size="sm" :variant="$tab === $name ? 'primary' : 'secondary'" wire:click="setTab('{{ $name }}')">{{ __('admin_contact.tabs.'.$name) }}</x-button>
        @endforeach
    </div>

    <div class="mt-4">
        @if ($items->isEmpty())
            <x-empty-state
                :title="__('admin_contact.empty.'.$tab.'.title')"
                :text="__('admin_contact.empty.'.$tab.'.text')"
            />
        @else
            <ul class="space-y-3">
                @foreach ($items as $item)
                    <li wire:key="contact-{{ $item->id }}">
                        <x-card variant="small" padding="p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="min-w-0 space-y-1">
                                    <p class="break-words text-lg font-semibold text-stone-900">{{ $item->subject }}</p>
                                    <p class="break-words text-stone-700">{{ __('admin_contact.list.from', ['name' => $item->name]) }}</p>
                                    <p class="flex flex-wrap items-center gap-2 text-sm text-stone-600">
                                        <x-badge :variant="$item->status->badge()">{{ $item->status->label() }}</x-badge>
                                        <span>{{ __('admin_contact.list.sent', ['time' => $item->created_at?->diffForHumans() ?? '—']) }}</span>
                                    </p>
                                </div>
                                <x-button :href="route('admin.contact.show', $item->code)" size="sm">
                                    {{ __('admin_contact.list.open_button') }}
                                </x-button>
                            </div>
                        </x-card>
                    </li>
                @endforeach
            </ul>

            <div class="mt-4 flex items-center justify-between gap-3">
                <x-button variant="secondary" size="sm" wire:click="previousPage" :disabled="$items->onFirstPage()">
                    {{ __('admin_contact.list.previous') }}
                </x-button>
                <x-button variant="secondary" size="sm" wire:click="nextPage" :disabled="! $items->hasMorePages()">
                    {{ __('admin_contact.list.next') }}
                </x-button>
            </div>
        @endif
    </div>
</div>
