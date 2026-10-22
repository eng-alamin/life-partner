<div class="grid gap-4 sm:grid-cols-2">
    <x-field name="age_min" :label="__('biodata.fields.age_min')" :required="true">
        <x-input name="age_min" type="text" wire:model="age_min" inputmode="numeric" maxlength="2" />
    </x-field>
    <x-field name="age_max" :label="__('biodata.fields.age_max')" :required="true">
        <x-input name="age_max" type="text" wire:model="age_max" inputmode="numeric" maxlength="2" />
    </x-field>
    <x-field name="height_min_cm" :label="__('biodata.fields.height_min_cm')" :hint="__('biodata.hints.height_min')">
        <x-input name="height_min_cm" type="text" wire:model="height_min_cm" inputmode="numeric" maxlength="3" />
    </x-field>
    <x-field name="min_education_level" :label="__('biodata.fields.min_education_level')">
        <x-select name="min_education_level" wire:model="min_education_level">
            <option value="">{{ __('biodata.any') }}</option>
            @foreach ($options['education_level'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-select>
    </x-field>
    <x-field name="min_practicing_level" :label="__('biodata.fields.min_practicing_level')">
        <x-select name="min_practicing_level" wire:model="min_practicing_level">
            <option value="">{{ __('biodata.any') }}</option>
            @foreach ($options['practicing_level'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-select>
    </x-field>
    <x-field name="preferred_country" :label="__('biodata.fields.preferred_country')">
        <x-select name="preferred_country" wire:model.live="preferred_country">
            <option value="">{{ __('biodata.any') }}</option>
            @foreach ($countries as $code => $name)
                <option value="{{ $code }}">{{ $name }}</option>
            @endforeach
        </x-select>
    </x-field>

    @if ($preferred_country === \App\Support\Countries::HOME)
        <x-field name="preferred_district_id" :label="__('biodata.fields.preferred_district_id')">
            <x-select name="preferred_district_id" wire:model="preferred_district_id">
                <option value="">{{ __('biodata.any') }}</option>
                @foreach ($districts as $district)
                    <option value="{{ $district->id }}">{{ $district->name() }}</option>
                @endforeach
            </x-select>
        </x-field>
    @endif
</div>

<fieldset class="space-y-2">
    <legend class="font-medium text-stone-700">{{ __('biodata.fields.marital_statuses') }}</legend>
    <p class="text-sm text-stone-600">{{ __('biodata.hints.marital_statuses') }}</p>

    <div class="flex flex-wrap gap-3">
        @foreach ($options['marital_status'] as $value => $label)
            <label class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-stone-400 bg-white/70 px-3 py-2">
                <input type="checkbox" value="{{ $value }}" wire:model="marital_statuses" class="size-5 accent-brand-600">
                <span>{{ $label }}</span>
            </label>
        @endforeach
    </div>

    @error('marital_statuses')
        <p class="text-sm font-medium text-red-700" role="alert">{{ $message }}</p>
    @enderror
    @error('marital_statuses.*')
        <p class="text-sm font-medium text-red-700" role="alert">{{ $message }}</p>
    @enderror
</fieldset>

<p class="text-sm text-stone-600">{{ __('biodata.hints.preferences_any') }}</p>
