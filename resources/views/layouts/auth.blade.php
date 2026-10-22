@props(['title' => null, 'description' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.partials.head', ['noindex' => true])
</head>
<body class="flex min-h-dvh flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-brand-800 focus:shadow">{{ __('nav.skip_to_content') }}</a>

    <header class="mx-auto flex w-full max-w-md items-center justify-between px-4 pt-6">
        <x-logo />
        <x-language-switcher />
    </header>

    <main id="main" class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center px-4 py-8">
        @if (session('status'))
            <x-alert type="success" class="mb-4">{{ session('status') }}</x-alert>
        @endif

        @if (session('error'))
            <x-alert type="danger" class="mb-4">{{ session('error') }}</x-alert>
        @endif

        <x-card variant="strong" padding="p-6 sm:p-8">
            {{ $slot }}
        </x-card>
    </main>
</body>
</html>
