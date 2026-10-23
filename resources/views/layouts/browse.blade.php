{{--
    Layout for pages that both guests and members open (the search page).
    Members get the member layout, guests the public one. Always noindex.
--}}
@props(['title' => null, 'description' => null])

@auth
    <x-layouts::app :title="$title" :description="$description">{{ $slot }}</x-layouts::app>
@else
    <x-layouts::marketing :title="$title" :description="$description" :noindex="true">
        {{-- Public pages bring their own container; this page does not, so it gets one here. --}}
        <div class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
            {{ $slot }}
        </div>
    </x-layouts::marketing>
@endauth
