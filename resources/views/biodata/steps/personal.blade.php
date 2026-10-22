<div class="space-y-4">
    <x-field name="hobbies" :label="__('biodata.fields.hobbies')" :hint="__('biodata.hints.hobbies')">
        <x-input name="hobbies" type="text" wire:model="hobbies" maxlength="255" />
    </x-field>

    <x-field name="about_me" :label="__('biodata.fields.about_me')" :hint="__('biodata.hints.about_me')" :required="true">
        <x-textarea name="about_me" rows="7" wire:model="about_me" maxlength="1500" />
    </x-field>
</div>
