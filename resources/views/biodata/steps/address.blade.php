@php($abroad = \App\Support\Countries::isAbroad($present_country))

<div class="grid gap-4 sm:grid-cols-2">
    <x-field name="present_country" :label="__('biodata.fields.present_country')" :hint="__('biodata.hints.present_country')" :required="true">
        <x-select name="present_country" wire:model.live="present_country">
            @foreach ($countries as $code => $name)
                <option value="{{ $code }}">{{ $name }}</option>
            @endforeach
        </x-select>
    </x-field>

    @unless ($abroad)
        <x-field name="present_district_id" :label="__('biodata.fields.present_district')" :required="true">
            <x-select name="present_district_id" wire:model="present_district_id">
                <option value="">{{ __('biodata.choose') }}</option>
                @foreach ($districts as $district)
                    <option value="{{ $district->id }}">{{ $district->name() }}</option>
                @endforeach
            </x-select>
        </x-field>
    @endunless

    <x-field name="present_city" :label="$abroad ? __('biodata.fields.present_city_abroad') : __('biodata.fields.present_city')" :required="$abroad">
        <x-input name="present_city" type="text" wire:model="present_city" maxlength="100" autocomplete="address-level2" />
    </x-field>

    <x-field name="permanent_district_id" :label="__('biodata.fields.permanent_district')" :hint="__('biodata.hints.permanent_district')" :required="true">
        <x-select name="permanent_district_id" wire:model="permanent_district_id">
            <option value="">{{ __('biodata.choose') }}</option>
            @foreach ($districts as $district)
                <option value="{{ $district->id }}">{{ $district->name() }}</option>
            @endforeach
        </x-select>
    </x-field>
</div>

<p class="text-sm text-stone-600">{{ __('biodata.hints.address_privacy') }}</p>
