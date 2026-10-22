<div>
    <h1 class="text-2xl font-bold text-brand-800">{{ __('search.heading') }}</h1>
    <p class="mt-1 text-stone-600">{{ $isMember ? __('search.intro_member') : __('search.intro_guest') }}</p>

    @error('form')
        <x-alert type="danger" class="mt-4">{{ $message }}</x-alert>
    @enderror

    @if ($blocked === 'paused')
        <x-empty-state class="mt-4" :title="__('search.paused.title')" :text="__('search.paused.text')">
            <x-button :href="route('my-biodata')" size="lg">{{ __('search.paused.button') }}</x-button>
        </x-empty-state>
    @elseif ($blocked === 'need_biodata')
        <x-empty-state class="mt-4" :title="__('search.need_biodata.title')" :text="__('search.need_biodata.text')">
            <x-button :href="route('my-biodata')" size="lg">{{ __('search.need_biodata.button') }}</x-button>
        </x-empty-state>
    @else
        <x-card class="mt-4">
            <form wire:submit="search" class="space-y-4" novalidate>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @unless ($isMember)
                        <x-field name="type" :label="__('search.fields.type')" :required="true">
                            <x-select name="type" wire:model="type">
                                <option value="">{{ __('biodata.choose') }}</option>
                                @foreach ($options['type'] as $case)
                                    <option value="{{ $case->value }}">{{ $case->label() }}</option>
                                @endforeach
                            </x-select>
                        </x-field>
                    @endunless

                    <x-field name="age_min" :label="__('search.fields.age_min')">
                        <x-input name="age_min" type="text" inputmode="numeric" maxlength="3" wire:model="age_min" />
                    </x-field>

                    <x-field name="age_max" :label="__('search.fields.age_max')">
                        <x-input name="age_max" type="text" inputmode="numeric" maxlength="3" wire:model="age_max" />
                    </x-field>

                    <x-field name="district_id" :label="__('search.fields.district')">
                        <x-select name="district_id" wire:model="district_id">
                            <option value="">{{ __('biodata.any') }}</option>
                            @foreach ($districts as $district)
                                <option value="{{ $district->id }}">{{ $district->name() }}</option>
                            @endforeach
                        </x-select>
                    </x-field>

                    @if ($isMember)
                        <x-field name="height_min" :label="__('search.fields.height_min')">
                            <x-input name="height_min" type="text" inputmode="numeric" maxlength="3" wire:model="height_min" />
                        </x-field>

                        <x-field name="height_max" :label="__('search.fields.height_max')">
                            <x-input name="height_max" type="text" inputmode="numeric" maxlength="3" wire:model="height_max" />
                        </x-field>

                        <x-field name="marital_status" :label="__('search.fields.marital_status')">
                            <x-select name="marital_status" wire:model="marital_status">
                                <option value="">{{ __('biodata.any') }}</option>
                                @foreach ($options['marital_status'] as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-select>
                        </x-field>

                        <x-field name="country" :label="__('search.fields.country')">
                            <x-select name="country" wire:model="country">
                                <option value="">{{ __('biodata.any') }}</option>
                                @foreach ($options['countries'] as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-select>
                        </x-field>

                        <x-field name="min_education" :label="__('search.fields.min_education')">
                            <x-select name="min_education" wire:model="min_education">
                                <option value="">{{ __('biodata.any') }}</option>
                                @foreach ($options['min_education'] as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-select>
                        </x-field>

                        <x-field name="practicing_level" :label="__('search.fields.practicing_level')">
                            <x-select name="practicing_level" wire:model="practicing_level">
                                <option value="">{{ __('biodata.any') }}</option>
                                @foreach ($options['practicing_level'] as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-select>
                        </x-field>

                        <x-field name="profession" :label="__('search.fields.profession')" :hint="__('search.hints.profession')">
                            <x-input name="profession" type="text" maxlength="60" wire:model="profession" />
                        </x-field>
                    @endif
                </div>

                <div class="flex flex-wrap gap-3">
                    <x-button type="submit" wire:loading.attr="disabled" wire:target="search">{{ __('search.apply') }}</x-button>
                    <x-button type="button" variant="secondary" wire:click="clear">{{ __('search.clear') }}</x-button>
                </div>
            </form>
        </x-card>

        @if ($isMember)
            <x-card class="mt-4" variant="small" padding="p-4">
                <h2 class="font-semibold text-stone-900">{{ __('search.saved.heading') }}</h2>

                <form wire:submit="saveSearch" class="mt-3 flex flex-wrap items-end gap-3" novalidate>
                    <x-field name="saveName" :label="__('search.saved.name_label')" :hint="__('search.saved.name_hint')" class="min-w-56 flex-1">
                        <x-input name="saveName" type="text" maxlength="60" wire:model="saveName" />
                    </x-field>
                    <x-button type="submit" variant="secondary" wire:loading.attr="disabled" wire:target="saveSearch">{{ __('search.saved.save') }}</x-button>
                </form>

                @if ($savedSearches->isEmpty())
                    <p class="mt-3 text-stone-600">{{ __('search.saved.empty') }}</p>
                @else
                    <ul class="mt-3 space-y-2">
                        @foreach ($savedSearches as $saved)
                            <li wire:key="saved-{{ $saved->id }}" class="flex flex-wrap items-center justify-between gap-2">
                                <span class="font-medium text-stone-800">{{ $saved->name }}</span>
                                <span class="flex gap-2">
                                    <x-button type="button" variant="ghost" size="sm" wire:click="applySaved({{ $saved->id }})">{{ __('search.saved.load') }}</x-button>
                                    <x-button type="button" variant="ghost" size="sm" wire:click="deleteSaved({{ $saved->id }})" wire:confirm="{{ __('search.saved.confirm_delete') }}">{{ __('search.saved.delete') }}</x-button>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        @endif

        @if ($listNotice !== '')
            <x-alert type="success" class="mt-6">{{ $listNotice }}</x-alert>
        @endif

        <div class="mt-6">
            @if ($blocked === 'choose_type')
                <x-empty-state :title="__('search.choose_type.title')" :text="__('search.choose_type.text')" />
            @elseif ($cards === [])
                <x-empty-state :title="__('search.empty.title')" :text="__('search.empty.text')" />
            @else
                <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($cards as $card)
                        <li wire:key="card-{{ $card['code'] }}">
                            <x-biodata-card :card="$card" :href="route('biodatas.show', $card['code'])">
                                @if ($isMember)
                                    <x-button type="button" variant="ghost" size="sm" wire:click="toggleShortlist('{{ $card['code'] }}')" wire:loading.attr="disabled">
                                        {{ in_array($card['code'], $shortlistedCodes, true) ? __('lists.ui.remove_shortlist') : __('lists.ui.add_shortlist') }}
                                    </x-button>
                                    <x-button type="button" variant="ghost" size="sm" wire:click="ignore('{{ $card['code'] }}')" wire:confirm="{{ __('lists.ui.confirm_ignore') }}" wire:loading.attr="disabled">{{ __('lists.ui.ignore') }}</x-button>
                                @endif
                            </x-biodata-card>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-5 flex items-center justify-between gap-3">
                    <x-button type="button" variant="secondary" size="sm" wire:click="previous" :disabled="! $hasPrevious">
                        {{ __('search.previous') }}
                    </x-button>
                    <span class="text-stone-600">{{ __('search.page', ['page' => \App\Support\Digits::localize($currentPage)]) }}</span>
                    <x-button type="button" variant="secondary" size="sm" wire:click="next" :disabled="! $hasNext">
                        {{ __('search.next') }}
                    </x-button>
                </div>

                @if ($guestLimitReached)
                    <x-alert type="warning" class="mt-5">
                        <p class="font-semibold">{{ __('search.guest_limit.title') }}</p>
                        <p class="mt-1">{{ __('search.guest_limit.text') }}</p>
                        <div class="mt-3 flex flex-wrap gap-3">
                            @if (Route::has('register') && app(\App\Services\Settings\SiteSettings::class)->registrationOpen())
                                <x-button :href="route('register')" size="sm">{{ __('nav.register') }}</x-button>
                            @endif
                            @if (Route::has('login'))
                                <x-button :href="route('login')" variant="secondary" size="sm">{{ __('nav.login') }}</x-button>
                            @endif
                        </div>
                    </x-alert>
                @endif
            @endif
        </div>
    @endif
</div>
