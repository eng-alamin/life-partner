<x-layouts::marketing :title="$story->title()" :description="__('stories.description')">
    <article class="mx-auto w-full max-w-3xl px-4 py-10 sm:px-6 sm:py-14">
        <p class="text-sm font-medium text-gold-700">{{ $story->couple_label }}</p>
        <h1 class="mt-1 text-3xl font-bold leading-tight text-brand-900 sm:text-4xl">{{ $story->title() }}</h1>

        <x-card class="mt-6">
            {{-- SafeText escapes everything first; only headings, lists and bold are created. --}}
            {!! \App\Support\SafeText::toHtml($story->body()) !!}
        </x-card>

        <p class="mt-4 text-sm text-stone-600">{{ __('stories.consent_note') }}</p>

        <x-button :href="route('stories.index')" variant="secondary" class="mt-6">{{ __('stories.back') }}</x-button>
    </article>
</x-layouts::marketing>
