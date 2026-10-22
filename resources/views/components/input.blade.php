@props(['name', 'type' => 'text'])

@php
    $id = 'f-'.str_replace('.', '-', $name);
    $describedBy = $id.'-hint'.($errors->has($name) ? ' '.$id.'-error' : '');
@endphp

<input
    id="{{ $id }}"
    name="{{ $name }}"
    type="{{ $type }}"
    aria-describedby="{{ $describedBy }}"
    @if ($errors->has($name)) aria-invalid="true" @endif
    {{ $attributes->class([
        'block min-h-11 w-full rounded-xl border bg-white/70 px-3 py-2 text-stone-900 placeholder:text-stone-500 focus:bg-white',
        'border-stone-500' => ! $errors->has($name),
        'border-red-600' => $errors->has($name),
    ]) }}
>
