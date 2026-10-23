<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-brand-800">{{ __('search.profile.heading') }}</h1>
            <p class="mt-1 text-stone-700">
                {{ __('overview.code') }}:
                <span class="font-mono font-semibold text-stone-900">{{ $code }}</span>
            </p>
            @if (! empty($card['identity_verified']))
                <p class="mt-2"><x-badge variant="gold">&#10003; {{ __('identity.badge') }}</x-badge></p>
            @endif
        </div>
        <x-button :href="route('biodatas.index')" variant="secondary" size="sm">{{ __('search.profile.back') }}</x-button>
    </div>

    @if ($isOwner)
        <x-alert type="info" class="mt-4">{{ __('search.profile.own_note') }}</x-alert>
    @else
        <livewire:interest.send-interest-button :code="$code" :key="'interest-panel-'.$code" />
        <livewire:shortlist.list-buttons :code="$code" :key="'lists-'.$code" />
        <livewire:block.block-button :code="$code" :key="'block-'.$code" />
        <livewire:report.report-button :code="$code" :key="'report-'.$code" />
    @endif

    <div class="mt-4 space-y-4">
        @if ($photos !== [])
            <x-card wire:key="section-photos">
                <h2 class="text-lg font-semibold text-stone-900">{{ __('photos.profile_heading') }}</h2>

                <ul class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach ($photos as $photo)
                        <li wire:key="profile-photo-{{ $photo['code'] }}" class="space-y-1">
                            <img src="{{ $photo['thumb'] }}" alt="{{ __('photos.alt') }}" width="360" height="450" loading="lazy" class="aspect-[4/5] w-full rounded-xl object-cover">
                            @if ($photo['pending'])
                                <x-badge variant="gold">{{ __('enums.photo_status.pending') }}</x-badge>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </x-card>
        @endif

        @foreach ($sections as $key => $rows)
            <x-card wire:key="section-{{ $key }}">
                <h2 class="text-lg font-semibold text-stone-900">{{ __('search.profile.sections.'.$key) }}</h2>

                <dl class="mt-3 grid gap-x-6 gap-y-3 sm:grid-cols-2">
                    @foreach ($rows as $row)
                        <div class="{{ strlen($row['value']) > 60 ? 'sm:col-span-2' : '' }}">
                            <dt class="text-sm text-stone-600">{{ $row['label'] }}</dt>
                            <dd class="whitespace-pre-line font-medium text-stone-900">{{ $row['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-card>

            {{-- Education sits right after the general section. --}}
            @if ($key === 'general' && $educations !== [])
                <x-card wire:key="section-education">
                    <h2 class="text-lg font-semibold text-stone-900">{{ __('search.profile.sections.education') }}</h2>

                    <ul class="mt-3 space-y-3">
                        @foreach ($educations as $education)
                            <li>
                                <p class="font-medium text-stone-900">{{ $education['degree'] }}@if ($education['level']) <span class="text-stone-600">({{ $education['level'] }})</span>@endif</p>
                                <p class="text-stone-700">{{ $education['institution'] }}, {{ \App\Support\Digits::localize($education['year']) }}</p>
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            @endif
        @endforeach
    </div>
</div>
