<div class="grid gap-4 sm:grid-cols-2">
    <x-field name="marriage_timeline" :label="__('biodata.fields.marriage_timeline')" :required="true">
        <x-select name="marriage_timeline" wire:model="marriage_timeline">
            <option value="">{{ __('biodata.choose') }}</option>
            @foreach ($options['marriage_timeline'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-select>
    </x-field>
    <x-field name="spouse_work_after_marriage" :label="__('biodata.fields.spouse_work_after_marriage')" :required="true">
        <x-select name="spouse_work_after_marriage" wire:model="spouse_work_after_marriage">
            <option value="">{{ __('biodata.choose') }}</option>
            @foreach ($options['spouse_work_after_marriage'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-select>
    </x-field>
    <x-field name="living_arrangement" :label="__('biodata.fields.living_arrangement')" :required="true">
        <x-select name="living_arrangement" wire:model="living_arrangement">
            <option value="">{{ __('biodata.choose') }}</option>
            @foreach ($options['living_arrangement'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-select>
    </x-field>
</div>
