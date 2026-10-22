<div class="grid gap-4 sm:grid-cols-2">
    <x-field name="prayer_habit" :label="__('biodata.fields.prayer_habit')" :required="true">
        <x-select name="prayer_habit" wire:model="prayer_habit">
            <option value="">{{ __('biodata.choose') }}</option>
            @foreach ($options['prayer_habit'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-select>
    </x-field>
    <x-field name="quran_reading" :label="__('biodata.fields.quran_reading')" :required="true">
        <x-select name="quran_reading" wire:model="quran_reading">
            <option value="">{{ __('biodata.choose') }}</option>
            @foreach ($options['quran_reading'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-select>
    </x-field>
    <x-field name="madhhab" :label="__('biodata.fields.madhhab')" :required="true">
        <x-select name="madhhab" wire:model="madhhab">
            <option value="">{{ __('biodata.choose') }}</option>
            @foreach ($options['madhhab'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-select>
    </x-field>
    <x-field name="practicing_level" :label="__('biodata.fields.practicing_level')" :required="true">
        <x-select name="practicing_level" wire:model="practicing_level">
            <option value="">{{ __('biodata.choose') }}</option>
            @foreach ($options['practicing_level'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-select>
    </x-field>
    <x-field name="dress_practice" :label="__('biodata.fields.dress_practice')" :hint="__('biodata.hints.dress')" :required="true">
        <x-select name="dress_practice" wire:model="dress_practice">
            <option value="">{{ __('biodata.choose') }}</option>
            @foreach ($options['dress_practice'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-select>
    </x-field>
</div>
