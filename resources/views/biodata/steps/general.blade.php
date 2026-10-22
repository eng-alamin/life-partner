<div class="grid gap-4 sm:grid-cols-2">
    <x-field name="type" :label="__('biodata.fields.type')" :hint="$typeLocked ? __('biodata.hints.type_locked') : null" :required="true">
        <x-select name="type" wire:model="type" :disabled="$typeLocked">
            <option value="">{{ __('biodata.choose') }}</option>
            @foreach ($options['type'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-select>
    </x-field>

    <x-field name="marital_status" :label="__('biodata.fields.marital_status')" :required="true">
        <x-select name="marital_status" wire:model="marital_status">
            <option value="">{{ __('biodata.choose') }}</option>
            @foreach ($options['marital_status'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-select>
    </x-field>

    <x-field name="birth_date" :label="__('biodata.fields.birth_date')" :hint="__('biodata.hints.birth_date')" :required="true">
        <x-input name="birth_date" type="date" wire:model="birth_date" autocomplete="bday" />
    </x-field>

    <x-field name="height_cm" :label="__('biodata.fields.height_cm')" :hint="__('biodata.hints.height_cm')" :required="true">
        <x-input name="height_cm" type="text" inputmode="numeric" wire:model="height_cm" maxlength="3" />
    </x-field>

    <x-field name="weight_kg" :label="__('biodata.fields.weight_kg')">
        <x-input name="weight_kg" type="text" inputmode="numeric" wire:model="weight_kg" maxlength="3" />
    </x-field>

    <x-field name="complexion" :label="__('biodata.fields.complexion')">
        <x-select name="complexion" wire:model="complexion">
            <option value="">{{ __('biodata.choose') }}</option>
            @foreach ($options['complexion'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-select>
    </x-field>

    <x-field name="blood_group" :label="__('biodata.fields.blood_group')">
        <x-select name="blood_group" wire:model="blood_group">
            <option value="">{{ __('biodata.choose') }}</option>
            @foreach ($options['blood_group'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-select>
    </x-field>
</div>
