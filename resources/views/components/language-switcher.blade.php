{{--
    One-button language toggle. The button is a pill with both language names; a sliding thumb
    marks the active one. Pressing it posts the OTHER language (works without JavaScript).
    Supports any number of locales: it switches to the next one in config('lifepartner.locales').
--}}
@php
    $locales = config('lifepartner.locales');
    $codes = array_keys($locales);
    $current = app()->getLocale();
    $position = max(0, (int) array_search($current, $codes, true));
    $next = $codes[($position + 1) % count($codes)];
@endphp

<form method="POST" action="{{ route('locale.update') }}" {{ $attributes->class(['inline-flex']) }}>
    @csrf
    <input type="hidden" name="locale" value="{{ $next }}">

    <button
        type="submit"
        lang="{{ $next }}"
        aria-label="{{ __('common.switch_language', ['language' => $locales[$next]]) }}"
        title="{{ __('common.switch_language', ['language' => $locales[$next]]) }}"
        class="relative grid min-h-11 grid-flow-col auto-cols-fr items-center rounded-full border border-white/70 bg-white/55 p-1 text-sm font-semibold shadow-[inset_0_1px_0_rgb(255_255_255/0.9),0_4px_14px_-6px_rgb(19_80_66/0.35)] backdrop-blur transition hover:bg-white/80 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
    >
        {{-- Sliding thumb: moves by one slot per locale. --}}
        <span
            aria-hidden="true"
            class="absolute inset-y-1 start-1 rounded-full bg-linear-to-b from-brand-600 to-brand-800 shadow-md shadow-brand-900/30 transition-transform duration-300 ease-out motion-reduce:transition-none"
            style="width: calc((100% - 0.5rem) / {{ count($codes) }}); transform: translateX(calc({{ $position }} * 100%));"
        ></span>

        @foreach ($locales as $code => $name)
            <span lang="{{ $code }}" @if ($code === $current) aria-current="true" @endif class="relative z-10 px-3 py-1 text-center leading-6 transition-colors {{ $code === $current ? 'text-white' : 'text-stone-700' }}">{{ $name }}</span>
        @endforeach
    </button>
</form>
