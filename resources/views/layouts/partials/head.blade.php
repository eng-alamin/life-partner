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

{{--
    Admin only: choose light or dark before the first paint so the page never flashes.
    The choice is kept in this browser (localStorage) and defaults to the device setting.
--}}
@if (! empty($themeable))
    <meta name="color-scheme" content="light dark">
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('lp-theme');
                var dark = saved === null ? window.matchMedia('(prefers-color-scheme: dark)').matches : saved === 'dark';
                document.documentElement.classList.toggle('dark', dark);
            } catch (e) {}
        })();
    </script>
@endif

{{-- The Bangla font (Hind Siliguri) is self-hosted and comes with app.css. --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])
