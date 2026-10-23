{{--
    One number with a label, for dashboards. Use inside a <dl>.
    With `href` the whole card is clickable (the link text is `action`, or the label).
--}}
@props(['label', 'value', 'href' => null, 'action' => null])

<div {{ $attributes->class(['glass-sm relative p-4 transition', 'hover:-translate-y-0.5 hover:shadow-lg motion-reduce:hover:translate-y-0' => $href !== null]) }}>
    <dt class="text-sm font-medium text-stone-600">{{ $label }}</dt>
    <dd class="mt-1">
        <span class="block text-3xl font-bold text-brand-800">{{ $value }}</span>

        @if ($href !== null)
            <a href="{{ $href }}" class="mt-2 block text-sm font-semibold text-brand-700 after:absolute after:inset-0 after:rounded-[inherit] focus-visible:after:outline-2 focus-visible:after:outline-offset-2 focus-visible:after:outline-brand-600">{{ $action ?? $label }}</a>
        @endif
    </dd>
</div>
