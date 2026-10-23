<div>
    <h1 class="text-2xl font-bold text-brand-800">{{ __('admin_identity.queue.heading') }}</h1>
    <p class="mt-1 text-stone-600">{{ __('admin_identity.queue.intro') }}</p>

    <div class="mt-4 flex gap-2" role="group" aria-label="{{ __('admin_identity.queue.heading') }}">
        <x-button type="button" size="sm" :variant="$tab === 'review' ? 'primary' : 'secondary'" wire:click="setTab('review')">{{ __('admin_identity.queue.tab_review') }}</x-button>
        <x-button type="button" size="sm" :variant="$tab === 'decided' ? 'primary' : 'secondary'" wire:click="setTab('decided')">{{ __('admin_identity.queue.tab_decided') }}</x-button>
    </div>

    <div class="mt-4">
        @if ($rows === [])
            <x-empty-state
                :title="$tab === 'review' ? __('admin_identity.queue.empty_review_title') : __('admin_identity.queue.empty_decided_title')"
                :text="$tab === 'review' ? __('admin_identity.queue.empty_review_text') : __('admin_identity.queue.empty_decided_text')"
            />
        @else
            <ul class="space-y-3">
                @foreach ($rows as $row)
                    <li wire:key="identity-{{ $row['code'] }}">
                        <x-card variant="small" padding="p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="min-w-0 space-y-1">
                                    <p class="text-lg font-semibold text-stone-900">{{ $row['document']->label() }}</p>
                                    <p class="text-stone-700">
                                        {{ __('admin_identity.queue.member') }}:
                                        <span class="font-mono font-semibold" dir="ltr">{{ $row['member'] ?? '—' }}</span>
                                    </p>
                                    <p class="flex flex-wrap items-center gap-2 text-sm text-stone-600">
                                        <x-badge :variant="$row['status']->badge()">{{ $row['status']->label() }}</x-badge>
                                        @if ($row['when'])
                                            <span>{{ $row['when'] }}</span>
                                        @endif
                                    </p>
                                </div>
                                <x-button :href="route('admin.verification.show', $row['code'])" size="sm">
                                    {{ __('admin_identity.queue.open_button') }}
                                </x-button>
                            </div>
                        </x-card>
                    </li>
                @endforeach
            </ul>

            <div class="mt-4 flex items-center justify-between gap-3">
                <x-button variant="secondary" size="sm" wire:click="previousPage" :disabled="$items->onFirstPage()">
                    {{ __('admin_identity.queue.previous') }}
                </x-button>
                <x-button variant="secondary" size="sm" wire:click="nextPage" :disabled="! $items->hasMorePages()">
                    {{ __('admin_identity.queue.next') }}
                </x-button>
            </div>
        @endif
    </div>
</div>
