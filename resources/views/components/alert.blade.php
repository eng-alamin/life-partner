@props(['type' => 'info'])

@php
    $styles = [
        'info' => ['glass-mint', 'text-brand-900', 'status'],
        'success' => ['glass-mint', 'text-brand-900', 'status'],
        'warning' => ['glass-gold', 'text-gold-700', 'status'],
        'danger' => ['glass-red', 'text-red-900', 'alert'],
    ];

    [$surface, $text, $role] = $styles[$type] ?? $styles['info'];
@endphp

<div role="{{ $role }}" {{ $attributes->class([$surface, $text, 'p-4 text-base']) }}>
    {{ $slot }}
</div>
