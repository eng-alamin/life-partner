@props(['title', 'text' => null])

<div {{ $attributes->class(['glass p-8 text-center']) }}>
    <h2 class="text-lg font-semibold text-stone-900">{{ $title }}</h2>
    @if ($text)
        <p class="mx-auto mt-2 max-w-md text-stone-600">{{ $text }}</p>
    @endif
    @if (! $slot->isEmpty())
        <div class="mt-5 flex justify-center">{{ $slot }}</div>
    @endif
</div>
