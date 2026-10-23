{{--
    Light / dark switch (admin panel only). Plain button: resources/js/app.js flips the "dark"
    class on <html> and remembers the choice in this browser. Both icons are in the page;
    CSS shows the one that matches the state, so there is no flash and no server round trip.
--}}
<button
    type="button"
    data-theme-toggle
    data-label-to-dark="{{ __('common.theme.to_dark') }}"
    data-label-to-light="{{ __('common.theme.to_light') }}"
    aria-label="{{ __('common.theme.to_dark') }}"
    title="{{ __('common.theme.to_dark') }}"
    {{ $attributes->class(['inline-flex size-11 shrink-0 items-center justify-center rounded-full text-stone-700 transition hover:bg-white/70 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600']) }}
>
    {{-- Moon: shown in light mode (press for dark) --}}
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5 dark:hidden" aria-hidden="true" focusable="false">
        <path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5Z" />
    </svg>
    {{-- Sun: shown in dark mode (press for light) --}}
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="hidden size-5 dark:block" aria-hidden="true" focusable="false">
        <circle cx="12" cy="12" r="4" />
        <path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M18.4 5.6 17 7M7 17l-1.4 1.4" />
    </svg>
</button>
