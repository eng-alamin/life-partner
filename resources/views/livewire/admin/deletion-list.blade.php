<div>
    <h1 class="text-2xl font-bold text-brand-800">{{ __('admin_deletions.heading') }}</h1>
    <p class="mt-1 text-stone-600">{{ __('admin_deletions.intro') }}</p>

    <div class="mt-4">
        @if ($members->isEmpty())
            <x-empty-state :title="__('admin_deletions.empty_title')" :text="__('admin_deletions.empty_text')" />
        @else
            <ul class="space-y-3">
                @foreach ($members as $member)
                    @php
                        $due = $member->deletion_scheduled_for->isPast();
                        $daysLeft = (int) ceil(now()->diffInSeconds($member->deletion_scheduled_for, false) / 86400);
                        $held = $reasons[$member->id] ?? [];
                    @endphp
                    <li wire:key="deletion-{{ $member->id }}">
                        <x-card variant="small" padding="p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="min-w-0 space-y-1">
                                    <p class="font-mono text-lg font-semibold text-stone-900">
                                        {{ $codes[$member->id] ?? __('admin_deletions.no_biodata') }}
                                    </p>
                                    <p class="text-sm text-stone-600">
                                        {{ __('admin_deletions.requested', ['time' => $member->deletion_requested_at->diffForHumans()]) }}
                                    </p>
                                    <p class="text-sm text-stone-600">
                                        {{ __('admin_deletions.scheduled', ['date' => $member->deletion_scheduled_for->translatedFormat('j F Y')]) }}
                                    </p>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($held !== [])
                                        @foreach ($held as $reason)
                                            <x-badge variant="red">{{ __('admin_deletions.paused.'.$reason) }}</x-badge>
                                        @endforeach
                                    @elseif ($due)
                                        <x-badge variant="gold">{{ __('admin_deletions.due') }}</x-badge>
                                    @else
                                        <x-badge variant="brand">{{ __('admin_deletions.days_left', ['days' => max($daysLeft, 0)]) }}</x-badge>
                                    @endif
                                </div>
                            </div>
                        </x-card>
                    </li>
                @endforeach
            </ul>

            <div class="mt-4 flex items-center justify-between gap-3">
                <x-button variant="secondary" size="sm" wire:click="previousPage" :disabled="$members->onFirstPage()">
                    {{ __('admin_deletions.previous') }}
                </x-button>
                <x-button variant="secondary" size="sm" wire:click="nextPage" :disabled="! $members->hasMorePages()">
                    {{ __('admin_deletions.next') }}
                </x-button>
            </div>
        @endif
    </div>
</div>
