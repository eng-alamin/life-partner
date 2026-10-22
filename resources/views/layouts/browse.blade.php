{{--
    Layout for pages that both guests and members open (the search page).
    Members get the member layout, guests the public one. Always noindex.
--}}
@props(['title' => null, 'description' => null])

@auth
    <x-layouts::app :title="$title" :description="$description">{{ $slot }}</x-layouts::app>
@else
    <x-layouts::marketing :title="$title" :description="$description" :noindex="true">{{ $slot }}</x-layouts::marketing>
@endauth
