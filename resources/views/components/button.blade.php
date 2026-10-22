@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-xl font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 disabled:cursor-not-allowed disabled:opacity-60';

    $variants = [
        'primary' => 'bg-linear-to-b from-brand-600 to-brand-800 text-white shadow-md shadow-brand-900/25 hover:from-brand-700 hover:to-brand-900',
        'secondary' => 'border border-stone-300 bg-white/80 text-stone-800 hover:bg-white',
        'ghost' => 'text-brand-700 hover:bg-brand-50',
        'gold' => 'bg-linear-to-b from-gold-500 to-gold-700 text-white shadow-md shadow-gold-700/25 hover:brightness-110',
        'danger' => 'bg-red-700 text-white hover:bg-red-800',
        'light' => 'bg-white text-brand-800 shadow-lg shadow-black/10 hover:bg-brand-50',
    ];

    $sizes = [
        'sm' => 'min-h-11 px-3 py-1.5 text-sm',
        'md' => 'min-h-11 px-4 py-2',
        'lg' => 'min-h-12 px-7 py-3 text-lg',
    ];

    $classes = [$base, $variants[$variant] ?? $variants['primary'], $sizes[$size] ?? $sizes['md']];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
