<div>
    <h1 class="text-2xl font-bold text-brand-800">{{ __('admin_payments.queue.heading') }}</h1>
    <p class="mt-1 text-stone-600">{{ __('admin_payments.queue.intro') }}</p>

    <div class="mt-4 flex gap-2" role="group" aria-label="{{ __('admin_payments.queue.heading') }}">
        <x-button type="button" size="sm" :variant="$tab === 'review' ? 'primary' : 'secondary'" wire:click="setTab('review')">{{ __('admin_payments.queue.tab_review') }}</x-button>
        <x-button type="button" size="sm" :variant="$tab === 'decided' ? 'primary' : 'secondary'" wire:click="setTab('decided')">{{ __('admin_payments.queue.tab_decided') }}</x-button>
        <x-button type="button" size="sm" :variant="$tab === 'attention' ? 'primary' : 'secondary'" wire:click="setTab('attention')">{{ __('admin_payments.queue.tab_attention').($attentionCount > 0 ? ' ('.\App\Support\Digits::localize($attentionCount).')' : '') }}</x-button>
    </div>

    <div class="mt-4">
        @if ($rows === [])
            <x-empty-state
                :title="__('admin_payments.queue.empty_'.$tab.'_title')"
                :text="__('admin_payments.queue.empty_'.$tab.'_text')"
            />
        @else
            <ul class="space-y-3">
                @foreach ($rows as $row)
                    <li wire:key="payment-{{ $row['code'] }}">
                        <x-card variant="small" padding="p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="min-w-0 space-y-1">
                                    <p class="text-lg font-semibold text-stone-900">{{ $row['plan'] }} · {{ $row['amount'] }}</p>
                                    <p class="text-stone-700">
                                        {{ __('admin_payments.queue.member') }}:
                                        <span class="font-mono font-semibold" dir="ltr">{{ $row['member'] ?? '—' }}</span>
                                    </p>
                                    <p class="flex flex-wrap items-center gap-2 text-sm text-stone-600">
                                        <x-badge :variant="$row['status']->badge()">{{ $row['status']->label() }}</x-badge>
                                        <span>{{ $row['has_trx'] ? __('admin_payments.queue.has_trx') : __('admin_payments.queue.no_trx') }}</span>
                                        <span>{{ $row['has_proof'] ? __('admin_payments.queue.has_proof') : __('admin_payments.queue.no_proof') }}</span>
                                        @if ($row['when'])
                                            <span>{{ $row['when'] }}</span>
                                        @endif
                                    </p>
                                </div>
                                <x-button :href="route('admin.payments.show', $row['code'])" size="sm">
                                    {{ __('admin_payments.queue.open_button') }}
                                </x-button>
                            </div>
                        </x-card>
                    </li>
                @endforeach
            </ul>

            <div class="mt-4 flex items-center justify-between gap-3">
                <x-button variant="secondary" size="sm" wire:click="previousPage" :disabled="$items->onFirstPage()">
                    {{ __('admin_payments.queue.previous') }}
                </x-button>
                <x-button variant="secondary" size="sm" wire:click="nextPage" :disabled="! $items->hasMorePages()">
                    {{ __('admin_payments.queue.next') }}
                </x-button>
            </div>
        @endif
    </div>
</div>
