<x-layouts::marketing :title="__('stories.title')" :description="__('stories.description')">
    <section class="mx-auto w-full max-w-5xl px-4 py-10 sm:px-6 sm:py-14">
        <h1 class="text-3xl font-bold leading-tight text-brand-900 sm:text-4xl">{{ __('stories.heading') }}</h1>
        <p class="mt-2 max-w-2xl text-stone-700">{{ __('stories.intro') }}</p>

        @if ($stories->isEmpty())
            <x-card class="mt-8">
                <p class="font-semibold text-stone-900">{{ __('stories.empty_title') }}</p>
                <p class="mt-1 text-stone-700">{{ __('stories.empty_text') }}</p>
            </x-card>
        @else
            <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($stories as $story)
                    <li>
                        <x-card variant="small" padding="p-5" class="h-full">
                            <p class="text-sm font-medium text-gold-700">{{ $story->couple_label }}</p>
                            <h2 class="mt-1 text-lg font-semibold text-stone-900">{{ $story->title() }}</h2>
                            <p class="mt-2 text-stone-700">{{ \Illuminate\Support\Str::limit(preg_replace('/\s+/u', ' ', $story->body()) ?? '', 180) }}</p>
                            <x-button :href="route('stories.show', $story->code)" variant="secondary" size="sm" class="mt-4">{{ __('stories.read_more') }}</x-button>
                        </x-card>
                    </li>
                @endforeach
            </ul>

            @if ($stories->hasPages())
                <div class="mt-6 flex items-center justify-between gap-3">
                    @if ($stories->previousPageUrl())
                        <x-button :href="$stories->previousPageUrl()" variant="secondary" size="sm">{{ __('stories.previous') }}</x-button>
                    @else
                        <span></span>
                    @endif
                    @if ($stories->nextPageUrl())
                        <x-button :href="$stories->nextPageUrl()" variant="secondary" size="sm">{{ __('stories.next') }}</x-button>
                    @endif
                </div>
            @endif
        @endif

        <p class="mt-8 text-sm text-stone-600">{{ __('stories.consent_note') }}</p>
    </section>
</x-layouts::marketing>
