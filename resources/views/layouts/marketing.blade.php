@props(['title' => null, 'description' => null, 'noindex' => false])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.partials.head', ['noindex' => $noindex])
</head>
<body class="flex min-h-dvh flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-brand-800 focus:shadow">{{ __('nav.skip_to_content') }}</a>

    @php
        $menu = [
            'home' => 'nav.home',
            'biodatas.index' => 'nav.biodatas',
            'pages.how-it-works' => 'nav.how_it_works',
            'pages.about' => 'nav.about',
            'pages.faq' => 'nav.faq',
            'pages.contact' => 'nav.contact',
        ];

        $registrationOpen = app(\App\Services\Settings\SiteSettings::class)->registrationOpen();

        $policyLinks = ['pages.privacy' => 'footer.privacy', 'pages.terms' => 'footer.terms', 'pages.refund' => 'footer.refund'];

        // A content page is linked only while it is published, so no link ever leads to a 404.
        $published = \App\Models\Page::publishedSlugs();
        $isLive = fn (string $route): bool => ! str_starts_with($route, 'pages.') || in_array(substr($route, 6), $published, true);
        $menu = array_filter($menu, $isLive, ARRAY_FILTER_USE_KEY);
        $policyLinks = array_filter($policyLinks, $isLive, ARRAY_FILTER_USE_KEY);

        // The success stories link shows only while at least one story is public, so it never leads to an empty page.
        if (\App\Models\SuccessStory::hasPublished()) {
            $menu['stories.index'] = 'nav.stories';
        }
    @endphp

    <header class="sticky top-0 z-40 glass-bar">
        <div class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
            <x-logo />

            <nav aria-label="{{ __('nav.main_menu') }}" class="hidden items-center gap-1 lg:flex">
                @foreach ($menu as $route => $label)
                    <x-nav-link :route="$route">{{ __($label) }}</x-nav-link>
                @endforeach
            </nav>

            <div class="flex items-center gap-2">
                <x-language-switcher />
                @if (Route::has('login'))
                    <x-button :href="route('login')" variant="ghost" class="hidden xl:inline-flex">{{ __('nav.login') }}</x-button>
                @endif
                @if (Route::has('register') && $registrationOpen)
                    <x-button :href="route('register')" class="hidden xl:inline-flex">{{ __('nav.register') }}</x-button>
                @endif
            </div>
        </div>
    </header>

    <main id="main" class="flex-1 pb-20 lg:pb-0">
        {{ $slot }}
    </main>

    <footer class="glass-bar glass-bar-bottom">
        <div class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="grid gap-8 md:grid-cols-3">
                <div>
                    <x-logo />
                    <p class="mt-3 text-stone-600">{{ __('footer.about') }}</p>
                    <x-contact-details compact class="mt-4" />
                </div>

                <nav aria-label="{{ __('footer.quick_links') }}">
                    <h2 class="text-base font-semibold text-stone-900">{{ __('footer.quick_links') }}</h2>
                    <ul class="mt-3 space-y-2">
                        @foreach ($menu as $route => $label)
                            @if (Route::has($route))
                                <li><a href="{{ route($route) }}" class="text-stone-600 hover:text-brand-700">{{ __($label) }}</a></li>
                            @endif
                        @endforeach
                    </ul>
                </nav>

                <nav aria-label="{{ __('footer.policies') }}">
                    <h2 class="text-base font-semibold text-stone-900">{{ __('footer.policies') }}</h2>
                    <ul class="mt-3 space-y-2">
                        @foreach ($policyLinks as $route => $label)
                            @if (Route::has($route))
                                <li><a href="{{ route($route) }}" class="text-stone-600 hover:text-brand-700">{{ __($label) }}</a></li>
                            @endif
                        @endforeach
                    </ul>
                </nav>
            </div>

            <p class="glass-gold mt-8 rounded-2xl p-4 text-sm text-gold-700">{{ __('footer.disclaimer') }}</p>
            <p class="mt-6 text-sm text-stone-600">{{ __('footer.copyright', ['year' => \App\Support\Digits::localize(now()->year), 'app' => config('app.name')]) }}</p>
        </div>
    </footer>

    <nav aria-label="{{ __('nav.quick_navigation') }}" class="glass-bar glass-bar-bottom fixed inset-x-0 bottom-0 z-40 pb-[env(safe-area-inset-bottom)] lg:hidden">
        <ul class="grid grid-cols-3">
            <li><x-nav-link route="home" variant="bottom">{{ __('nav.home') }}</x-nav-link></li>
            <li><x-nav-link route="biodatas.index" variant="bottom">{{ __('nav.biodatas') }}</x-nav-link></li>
            <li>
                @if (Route::has('login'))
                    <x-nav-link route="login" variant="bottom">{{ __('nav.account') }}</x-nav-link>
                @else
                    <span class="flex min-h-14 flex-col items-center justify-center px-1 text-center text-sm font-medium text-stone-600">
                        <x-language-switcher />
                    </span>
                @endif
            </li>
        </ul>
    </nav>
</body>
</html>
