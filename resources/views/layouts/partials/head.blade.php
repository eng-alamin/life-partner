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

{{-- Bangla-capable font. Swap for self-hosted @fontsource/hind-siliguri once approved. --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">

@vite(['resources/css/app.css', 'resources/js/app.js'])
