<x-layouts::marketing :title="$page->title()" :description="__('home.description')">
    <article class="mx-auto w-full max-w-3xl px-4 py-10 sm:px-6 sm:py-14">
        <h1 class="text-3xl font-bold leading-tight text-brand-900 sm:text-4xl">{{ $page->title() }}</h1>

        <x-card class="mt-6">
            {{-- SafeText escapes everything first; only headings, lists and bold are created. --}}
            {!! \App\Support\SafeText::toHtml($page->body()) !!}
        </x-card>

        @if ($page->slug === 'contact')
            <x-contact-details class="mt-6" />
        @endif
    </article>
</x-layouts::marketing>
