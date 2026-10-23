{{-- Multi-step form progress. `steps` is a list of labels, `current` is 1-based. --}}
@props(['steps', 'current' => 1])

@php
    $total = count($steps);
    $current = max(1, min($total, (int) $current));
    $percent = (int) round($current / $total * 100);
@endphp

<div {{ $attributes->class(['glass p-5']) }}>
    <p id="stepper-label" class="text-stone-600" aria-live="polite">
        {{ __('common.step_of', [
            'current' => \App\Support\Digits::localize($current),
            'total' => \App\Support\Digits::localize($total),
        ]) }}
        <span class="font-semibold text-stone-900">{{ $steps[$current - 1] ?? '' }}</span>
    </p>

    <div
        class="mt-2 h-2 rounded-full bg-stone-200"
        role="progressbar"
        aria-labelledby="stepper-label"
        aria-valuemin="1"
        aria-valuemax="{{ $total }}"
        aria-valuenow="{{ $current }}"
    >
        <div class="h-2 rounded-full bg-brand-600 transition-all" style="width: {{ $percent }}%"></div>
    </div>

    <ol class="mt-4 hidden flex-wrap gap-2 lg:flex">
        @foreach ($steps as $index => $label)
            <li>
                <x-badge :variant="$index + 1 === $current ? 'brand' : 'stone'">
                    {{ \App\Support\Digits::localize($index + 1) }}. {{ $label }}
                </x-badge>
            </li>
        @endforeach
    </ol>
</div>
