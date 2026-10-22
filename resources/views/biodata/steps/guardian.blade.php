<x-alert type="info">{{ __('biodata.hints.guardian_privacy') }}</x-alert>

<div class="grid gap-4 sm:grid-cols-2">
    <x-field name="guardian_name" :label="__('biodata.fields.guardian_name')" :required="true">
        <x-input name="guardian_name" type="text" wire:model="guardian_name" maxlength="100" autocomplete="off" />
    </x-field>
    <x-field name="guardian_relation" :label="__('biodata.fields.guardian_relation')" :required="true">
        <x-select name="guardian_relation" wire:model="guardian_relation">
            <option value="">{{ __('biodata.choose') }}</option>
            @foreach ($options['guardian_relation'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-select>
    </x-field>
    <x-field name="guardian_phone" :label="__('biodata.fields.guardian_phone')" :hint="__('biodata.hints.guardian_phone')" :required="true">
        <x-input name="guardian_phone" type="tel" wire:model="guardian_phone" inputmode="tel" maxlength="20" autocomplete="off" />
    </x-field>
</div>

<label class="inline-flex min-h-11 items-center gap-3">
    <input type="checkbox" wire:model="guardian_can_approve" class="size-5 accent-brand-600">
    <span>{{ __('biodata.fields.guardian_can_approve') }}</span>
</label>
@error('guardian_can_approve')
    <p class="text-sm font-medium text-red-700" role="alert">{{ $message }}</p>
@enderror
