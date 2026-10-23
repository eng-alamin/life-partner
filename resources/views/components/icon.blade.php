{{--
    Small line icons drawn for this project (24 x 24, no icon package). Always decorative:
    the text next to the icon (or the button's aria-label) says what it means.
--}}
@props(['name'])

@php
    $icons = [
        'home' => ['M3 10.5 12 3l9 7.5', 'M5 9.5V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9.5'],
        'search' => ['M11 4a7 7 0 1 0 0 14 7 7 0 0 0 0-14Z', 'm20 20-3.6-3.6'],
        'mail' => ['M3 6.5A1.5 1.5 0 0 1 4.5 5h15A1.5 1.5 0 0 1 21 6.5v11a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 17.5Z', 'm3.5 7 8.5 6 8.5-6'],
        'bookmark' => ['M6 4.5A1.5 1.5 0 0 1 7.5 3h9A1.5 1.5 0 0 1 18 4.5V21l-6-4-6 4Z'],
        'id' => ['M4 5.5A1.5 1.5 0 0 1 5.5 4h13A1.5 1.5 0 0 1 20 5.5v13a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 18.5Z', 'M12 11a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z', 'M7.5 17c.6-2 2.3-3 4.5-3s3.9 1 4.5 3'],
        'shield' => ['M12 3 4 6v5.5c0 4.6 3.2 8.2 8 9.5 4.8-1.3 8-4.9 8-9.5V6Z', 'm8.8 12 2.2 2.2 4.4-4.4'],
        'users' => ['M9 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z', 'M2.5 20c.5-3.3 3.1-5 6.5-5s6 1.7 6.5 5', 'M16 4.4a3.5 3.5 0 0 1 0 6.2', 'M18 15.3c1.9.6 3.1 2 3.5 4.7'],
        'wallet' => ['M3 7.5A2.5 2.5 0 0 1 5.5 5h13A2.5 2.5 0 0 1 21 7.5v9a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 16.5Z', 'M3 10h18', 'M7 15h3'],
        'sliders' => ['M4 7h9', 'M17 7h3', 'M15 5a2 2 0 1 1 0 4 2 2 0 0 1 0-4Z', 'M4 17h3', 'M11 17h9', 'M9 15a2 2 0 1 1 0 4 2 2 0 0 1 0-4Z'],
        'logout' => ['M9.5 8 5.5 12l4 4', 'M5.5 12H16', 'M15 4h3.5A1.5 1.5 0 0 1 20 5.5v13a1.5 1.5 0 0 1-1.5 1.5H15'],
        'swap' => ['M7 7h12l-3-3', 'M17 17H5l3 3'],
        'card' => ['M3 7.5A2.5 2.5 0 0 1 5.5 5h13A2.5 2.5 0 0 1 21 7.5v9a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 16.5Z', 'M3 10h18'],
        'tag' => ['M3.5 12.5V5.5A1.5 1.5 0 0 1 5 4h7l8 8-8 8Z', 'M8 8.5h.01'],
        'dot' => ['M12 12h.01'],
    ];
@endphp

<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" {{ $attributes->class(['size-6 shrink-0']) }}>
    @foreach ($icons[$name] ?? $icons['dot'] as $path)
        <path d="{{ $path }}" />
    @endforeach
</svg>
