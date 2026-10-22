@props(['name', 'rows' => 4])

@php
    $id = 'f-'.str_replace('.', '-', $name);
    $describedBy = $id.'-hint'.($errors->has($name) ? ' '.$id.'-error' : '');
@endphp

<textarea
    id="{{ $id }}"
    name="{{ $name }}"
    rows="{{ $rows }}"
    aria-describedby="{{ $describedBy }}"
    @if ($errors->has($name)) aria-invalid="true" @endif
    {{ $attributes->class([
        'block w-full rounded-xl border bg-white/70 px-3 py-2 text-stone-900 placeholder:text-stone-500 focus:bg-white',
        'border-stone-500' => ! $errors->has($name),
        'border-red-600' => $errors->has($name),
    ]) }}
>{{ $slot }}</textarea>
