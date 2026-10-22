@error('educations')
    <x-alert type="danger">{{ $message }}</x-alert>
@enderror

<div class="space-y-4">
    @foreach ($educations as $i => $row)
        <div wire:key="education-{{ $i }}" class="glass-sm space-y-3 p-4">
            <div class="flex items-center justify-between gap-3">
                <h3 class="font-semibold text-stone-900">
                    {{ __('biodata.education_entry', ['number' => \App\Support\Digits::localize($i + 1)]) }}
                </h3>

                @if (count($educations) > 1 && ! $readOnly)
                    <x-button type="button" variant="ghost" size="sm" wire:click="removeEducation({{ $i }})">
                        {{ __('biodata.remove') }}
                    </x-button>
                @endif
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <x-field :name="'educations.'.$i.'.degree'" :label="__('biodata.fields.degree')" :hint="__('biodata.hints.degree')" :required="true">
                    <x-input :name="'educations.'.$i.'.degree'" type="text" wire:model="educations.{{ $i }}.degree" maxlength="120" />
                </x-field>

                <x-field :name="'educations.'.$i.'.level'" :label="__('biodata.fields.education_level')" :hint="__('biodata.hints.education_level')" :required="true">
                    <x-select :name="'educations.'.$i.'.level'" wire:model="educations.{{ $i }}.level">
                        <option value="">{{ __('biodata.choose') }}</option>
                        @foreach ($options['education_level'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field :name="'educations.'.$i.'.institution'" :label="__('biodata.fields.institution')" :required="true">
                    <x-input :name="'educations.'.$i.'.institution'" type="text" wire:model="educations.{{ $i }}.institution" maxlength="150" />
                </x-field>

                <x-field :name="'educations.'.$i.'.passing_year'" :label="__('biodata.fields.passing_year')" :hint="__('biodata.hints.passing_year')" :required="true">
                    <x-input :name="'educations.'.$i.'.passing_year'" type="text" inputmode="numeric" wire:model="educations.{{ $i }}.passing_year" maxlength="4" />
                </x-field>
            </div>
        </div>
    @endforeach

    @if (count($educations) < $maxEducations && ! $readOnly)
        <x-button type="button" variant="secondary" wire:click="addEducation">{{ __('biodata.add_education') }}</x-button>
    @endif
</div>
