<div class="grid gap-4 sm:grid-cols-2">
    <x-field name="father_status" :label="__('biodata.fields.father_status')" :required="true">
        <x-select name="father_status" wire:model="father_status">
            <option value="">{{ __('biodata.choose') }}</option>
            @foreach ($options['parent_status'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-select>
    </x-field>
    <x-field name="mother_status" :label="__('biodata.fields.mother_status')" :required="true">
        <x-select name="mother_status" wire:model="mother_status">
            <option value="">{{ __('biodata.choose') }}</option>
            @foreach ($options['parent_status'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-select>
    </x-field>
    <x-field name="brothers_count" :label="__('biodata.fields.brothers_count')" :required="true">
        <x-input name="brothers_count" type="text" wire:model="brothers_count" inputmode="numeric" maxlength="2" />
    </x-field>
    <x-field name="sisters_count" :label="__('biodata.fields.sisters_count')" :required="true">
        <x-input name="sisters_count" type="text" wire:model="sisters_count" inputmode="numeric" maxlength="2" />
    </x-field>
    <x-field name="family_financial_status" :label="__('biodata.fields.family_financial_status')" :required="true">
        <x-select name="family_financial_status" wire:model="family_financial_status">
            <option value="">{{ __('biodata.choose') }}</option>
            @foreach ($options['family_financial_status'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-select>
    </x-field>
</div>
