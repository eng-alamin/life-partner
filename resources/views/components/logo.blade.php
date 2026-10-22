@props(['href' => null, 'size' => 'size-9'])

<a href="{{ $href ?? route('home') }}" {{ $attributes->class(['inline-flex items-center gap-2 rounded-lg']) }} aria-label="{{ config('app.name') }}">
    <svg viewBox="0 0 40 40" class="{{ $size }}" fill="none" aria-hidden="true">
        <circle cx="15" cy="20" r="10" class="stroke-brand-700" stroke-width="3" />
        <circle cx="25" cy="20" r="10" class="stroke-gold-500" stroke-width="3" />
    </svg>
    <span class="text-xl font-bold tracking-tight text-brand-800">{{ config('app.name') }}</span>
</a>
