@props(['variant' => 'glass', 'padding' => 'p-5 sm:p-6'])

@php
    $variants = [
        'glass' => 'glass',
        'small' => 'glass-sm',
        'strong' => 'glass-strong',
        'mint' => 'glass-mint',
        'gold' => 'glass-gold',
        'danger' => 'glass-red',
        'dark' => 'glass-dark',
    ];
@endphp

<div {{ $attributes->class([$variants[$variant] ?? 'glass', $padding]) }}>
    {{ $slot }}
</div>
