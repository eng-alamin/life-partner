{{--
    Label + control + hint + error. The control inside must use the same `name`
    (see <x-input>, <x-select>, <x-textarea>) so the ids line up.
--}}
@props([
    'name',
    'label',
    'hint' => null,
    'required' => false,
])

@php
    $id = 'f-'.str_replace('.', '-', $name);
@endphp

<div {{ $attributes->class(['space-y-1.5']) }}>
    <label for="{{ $id }}" class="block font-medium text-stone-700">
        {{ $label }}
        @if ($required)
            <span class="text-red-700" aria-hidden="true">*</span>
            <span class="sr-only">{{ __('common.required') }}</span>
        @endif
    </label>

    {{ $slot }}

    @if ($hint)
        <p id="{{ $id }}-hint" class="text-sm text-stone-600">{{ $hint }}</p>
    @endif

    @error($name)
        <p id="{{ $id }}-error" class="text-sm font-medium text-red-700" role="alert">{{ $message }}</p>
    @enderror
</div>
