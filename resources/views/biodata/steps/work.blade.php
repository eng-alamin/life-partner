<div class="grid gap-4 sm:grid-cols-2">
    <x-field name="profession" :label="__('biodata.fields.profession')" :required="true">
        <x-input name="profession" type="text" wire:model="profession" maxlength="120" />
    </x-field>
    <x-field name="employer" :label="__('biodata.fields.employer')" :hint="__('biodata.hints.employer')">
        <x-input name="employer" type="text" wire:model="employer" maxlength="150" />
    </x-field>
    <x-field name="income_range" :label="__('biodata.fields.income_range')" :hint="__('biodata.hints.income')" :required="true">
        <x-select name="income_range" wire:model="income_range">
            <option value="">{{ __('biodata.choose') }}</option>
            @foreach ($options['income_range'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-select>
    </x-field>
</div>
