@props(['variant' => 'stone'])

@php
    $variants = [
        'brand' => 'bg-brand-100 text-brand-800',
        'gold' => 'border border-gold-200 bg-gold-50 text-gold-700',
        'red' => 'bg-red-50 text-red-800',
        'stone' => 'bg-stone-100 text-stone-700',
    ];
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-0.5 text-sm font-medium', $variants[$variant] ?? $variants['stone']]) }}>{{ $slot }}</span>
