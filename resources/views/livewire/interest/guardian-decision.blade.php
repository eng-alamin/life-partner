<div>
    <h1 class="text-2xl font-bold text-brand-800">{{ __('guardian.page.heading') }}</h1>

    @if ($outcome === 'approved')
        <x-alert type="success" class="mt-4">{{ __('guardian.page.done_approved') }}</x-alert>
    @elseif ($outcome === 'declined')
        <x-alert type="success" class="mt-4">{{ __('guardian.page.done_declined') }}</x-alert>
    @elseif (! $open)
        <x-alert type="warning" class="mt-4">{{ __('guardian.errors.invalid_link') }}</x-alert>
    @else
        <p class="mt-2 text-stone-700">{{ __('guardian.page.intro') }}</p>

        @error('form')
            <x-alert type="danger" class="mt-4">{{ $message }}</x-alert>
        @enderror

        <div class="mt-4 rounded-xl border border-stone-200 bg-white/70 p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="font-mono text-lg font-semibold text-stone-900">{{ $card['code'] }}</p>
                <x-badge variant="brand">{{ $card['type'] }}</x-badge>
            </div>
            <p class="mt-1 text-stone-700">
                @if ($card['age'] !== null)
                    {{ __('search.card.age', ['age' => \App\Support\Digits::localize($card['age'])]) }}@if ($card['marital_status']) · {{ $card['marital_status'] }}@endif
                @endif
                @if ($card['location'] !== '')
                    · {{ $card['location'] }}
                @endif
                @if (filled($card['profession']))
                    · {{ $card['profession'] }}
                @endif
            </p>
        </div>

        <p class="mt-4 text-sm text-stone-600">{{ __('guardian.page.note') }}</p>

        <div class="mt-4 flex flex-col gap-2 sm:flex-row">
            <x-button size="lg" class="flex-1" wire:click="approve" wire:loading.attr="disabled">{{ __('guardian.page.approve') }}</x-button>
            <x-button variant="secondary" size="lg" class="flex-1" wire:click="decline" wire:confirm="{{ __('guardian.page.confirm_decline') }}" wire:loading.attr="disabled">{{ __('guardian.page.decline') }}</x-button>
        </div>
    @endif
</div>
