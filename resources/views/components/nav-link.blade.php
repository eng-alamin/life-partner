{{--
    Renders a navigation link only when its route exists, so menus grow as
    modules are added and never point to a missing page.
    variant: top (desktop bar), bottom (phone tab bar), side (admin sidebar)
    count: a number of things waiting; shown as a small badge when above zero
--}}
@props(['route', 'variant' => 'top', 'count' => 0])

@if (Route::has($route))
    @php
        $active = request()->routeIs($route, $route.'.*');

        $base = [
            'top' => 'rounded-lg px-3 py-2 font-medium',
            'bottom' => 'flex min-h-14 flex-col items-center justify-center px-1 text-center text-sm leading-tight font-medium',
            'side' => 'block rounded-lg px-3 py-2.5 font-medium',
        ][$variant] ?? 'rounded-lg px-3 py-2 font-medium';

        $state = [
            'top' => $active ? 'bg-brand-50 text-brand-800' : 'text-stone-700 hover:bg-white/70',
            'bottom' => $active ? 'text-brand-700' : 'text-stone-600',
            'side' => $active ? 'bg-brand-50 text-brand-800' : 'text-stone-700 hover:bg-white/70',
        ][$variant] ?? '';
    @endphp

    <a href="{{ route($route) }}" @if ($active) aria-current="page" @endif {{ $attributes->class([$base, $state]) }}>
        {{ $slot }}
        @if ($count > 0)
            <x-badge variant="gold" class="ms-2"><span aria-hidden="true">{{ $count > 99 ? '99+' : $count }}</span><span class="sr-only">{{ __('admin.waiting.badge_sr', ['count' => $count]) }}</span></x-badge>
        @endif
    </a>
@endif
