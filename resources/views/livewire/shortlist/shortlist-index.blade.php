<div>
    <h1 class="text-2xl font-bold text-brand-800">{{ __('lists.ui.heading') }}</h1>
    <p class="mt-1 text-stone-700">{{ __('lists.ui.intro') }}</p>

    <div class="mt-4 flex gap-2" role="tablist" aria-label="{{ __('lists.ui.heading') }}">
        @foreach (['shortlist', 'ignored'] as $name)
            <button
                type="button"
                role="tab"
                wire:click="showTab('{{ $name }}')"
                aria-selected="{{ $tab === $name ? 'true' : 'false' }}"
                @class([
                    'min-h-11 flex-1 rounded-xl px-4 py-2 font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 sm:flex-none',
                    'bg-brand-700 text-white' => $tab === $name,
                    'border border-stone-300 bg-white/80 text-stone-800 hover:bg-white' => $tab !== $name,
                ])
            >
                {{ __('lists.ui.tab_'.$name) }}
            </button>
        @endforeach
    </div>

    @if ($notice !== '')
        <x-alert type="success" class="mt-4">{{ $notice }}</x-alert>
    @endif

    @error('form')
        <x-alert type="danger" class="mt-4">{{ $message }}</x-alert>
    @enderror

    @if ($paused)
        <x-empty-state class="mt-4" :title="__('search.paused.title')" :text="__('search.paused.text')">
            <x-button :href="route('my-biodata')" size="sm">{{ __('search.paused.button') }}</x-button>
        </x-empty-state>
    @elseif ($cards === [])
        <x-empty-state class="mt-4" :title="__('lists.ui.empty_'.$tab.'_title')" :text="__('lists.ui.empty_'.$tab.'_text')">
            <x-button :href="route('biodatas.index')" size="sm">{{ __('interests.ui.find_matches') }}</x-button>
        </x-empty-state>
    @else
        <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($cards as $card)
                <li wire:key="list-{{ $tab }}-{{ $card['code'] }}">
                    <x-biodata-card :card="$card" :href="route('biodatas.show', $card['code'])">
                        @if ($tab === 'shortlist')
                            <x-button type="button" variant="ghost" size="sm" wire:click="remove('{{ $card['code'] }}')" wire:loading.attr="disabled">{{ __('lists.ui.remove_shortlist') }}</x-button>
                        @else
                            <x-button type="button" variant="ghost" size="sm" wire:click="restore('{{ $card['code'] }}')" wire:loading.attr="disabled">{{ __('lists.ui.restore') }}</x-button>
                        @endif
                    </x-biodata-card>
                </li>
            @endforeach
        </ul>
    @endif

    @if (Route::has('blocks.index'))
        <p class="mt-6"><a href="{{ route('blocks.index') }}" class="font-medium text-brand-700 underline underline-offset-2">{{ __('lists.ui.blocked_link') }}</a></p>
    @endif
</div>
