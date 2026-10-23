{{--
    One search result. $card comes from BiodataVisibility::card(), so it holds
    only fields that guests and members may see.
--}}
@props(['card', 'href'])

<x-card variant="small" padding="p-4" class="h-full">
    @if (! empty($card['photo_url']))
        <img src="{{ $card['photo_url'] }}" alt="{{ __('photos.alt') }}" width="360" height="450" loading="lazy" class="mb-3 aspect-[4/5] w-full rounded-xl object-cover">
    @endif

    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="font-mono text-lg font-semibold text-stone-900">{{ $card['code'] }}</p>
        <x-badge variant="brand">{{ $card['type'] }}</x-badge>
    </div>

    @if (! empty($card['identity_verified']))
        <p class="mt-2"><x-badge variant="gold">&#10003; {{ __('identity.badge') }}</x-badge></p>
    @endif

    <dl class="mt-3 space-y-1 text-stone-700">
        @if ($card['age'] !== null)
            <div>
                <dt class="sr-only">{{ __('biodata.fields.age') }}</dt>
                <dd>{{ __('search.card.age', ['age' => \App\Support\Digits::localize($card['age'])]) }}@if ($card['marital_status']) · {{ $card['marital_status'] }}@endif</dd>
            </div>
        @endif

        @if ($card['location'] !== '')
            <div>
                <dt class="sr-only">{{ __('biodata.fields.location') }}</dt>
                <dd>{{ $card['location'] }}</dd>
            </div>
        @endif

        @if (filled($card['profession']))
            <div>
                <dt class="sr-only">{{ __('biodata.fields.profession') }}</dt>
                <dd>{{ $card['profession'] }}</dd>
            </div>
        @endif

        @if (filled($card['practicing_level']))
            <div>
                <dt class="sr-only">{{ __('biodata.fields.practicing_level') }}</dt>
                <dd>{{ $card['practicing_level'] }}</dd>
            </div>
        @endif
    </dl>

    <div class="mt-4 flex flex-wrap items-center gap-2">
        <x-button :href="$href" variant="secondary" size="sm">{{ __('search.card.view') }}</x-button>
        {{ $slot }}
    </div>
</x-card>
