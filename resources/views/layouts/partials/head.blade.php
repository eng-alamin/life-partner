<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#187c60">
@if (! empty($noindex))
    <meta name="robots" content="noindex, nofollow">
@endif
<title>{{ filled($title ?? null) ? $title.' | ' : '' }}{{ config('app.name') }}</title>
@if (filled($description ?? null))
    <meta name="description" content="{{ $description }}">
@endif

{{-- The Bangla font (Hind Siliguri) is self-hosted and comes with app.css. --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])
