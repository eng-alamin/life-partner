@props(['title' => null, 'description' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.partials.head', ['noindex' => true])
</head>
<body class="flex min-h-dvh flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-brand-800 focus:shadow">{{ __('nav.skip_to_content') }}</a>

    @php
        $menu = [
            'dashboard' => 'nav.dashboard',
            'biodatas.index' => 'nav.search',
            'interests.index' => 'nav.interests',
            'shortlist.index' => 'nav.shortlist',
            'my-biodata' => 'nav.my_biodata',
        ];
        $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr((string) auth()->user()?->name, 0, 1));
    @endphp

    <header class="sticky top-0 z-40 glass-bar">
        <div class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
            <x-logo :href="Route::has('dashboard') ? route('dashboard') : route('home')" />

            <nav aria-label="{{ __('nav.member_menu') }}" class="hidden items-center gap-1 lg:flex">
                @foreach ($menu as $route => $label)
                    <x-nav-link :route="$route">{{ __($label) }}</x-nav-link>
                @endforeach
            </nav>

            <div class="flex items-center gap-1">
                <x-language-switcher />
                <x-notification-bell />
                @if (Route::has('settings'))
                    <a href="{{ route('settings') }}" class="inline-flex size-11 items-center justify-center rounded-full bg-brand-100 font-semibold text-brand-800" aria-label="{{ __('nav.settings') }}">{{ $initial !== '' ? $initial : '•' }}</a>
                @endif
                @if (Route::has('logout'))
                    <form method="POST" action="{{ route('logout') }}" class="hidden lg:block">
                        @csrf
                        <x-button type="submit" variant="ghost" size="sm">{{ __('nav.logout') }}</x-button>
                    </form>
                @endif
            </div>
        </div>
    </header>

    <main id="main" class="mx-auto w-full max-w-6xl flex-1 px-4 pb-24 pt-6 sm:px-6 lg:px-8 lg:pb-10">
        @if (session('status'))
            <x-alert type="success" class="mb-4">{{ session('status') }}</x-alert>
        @endif

        {{ $slot }}
    </main>

    <nav aria-label="{{ __('nav.member_tabs') }}" class="glass-bar glass-bar-bottom fixed inset-x-0 bottom-0 z-40 pb-[env(safe-area-inset-bottom)] lg:hidden">
        <ul class="grid grid-cols-5">
            @foreach ($menu as $route => $label)
                <li><x-nav-link :route="$route" variant="bottom">{{ __($label) }}</x-nav-link></li>
            @endforeach
        </ul>
    </nav>
</body>
</html>
