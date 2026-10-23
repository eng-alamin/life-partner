{{--
    The shared frame of the member panel and the admin panel.
    Phones: floating top bar + floating bottom dock (and a strip of page chips for sections with several pages).
    Desktop (lg): floating side rail, floating top bar, and a second menu column for sections with several pages.
    panel: "member" or "admin".
--}}
@props(['panel' => 'member', 'title' => null])

@php
    $user = auth()->user();
    $isAdmin = $panel === 'admin';
    $sections = $isAdmin ? \App\Support\PanelMenu::admin($user) : \App\Support\PanelMenu::member();
    $current = collect($sections)->firstWhere('active', true);
    $pages = $current['items'] ?? [];
    $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr((string) $user?->name, 0, 1));
    $homeRoute = $isAdmin ? 'admin.dashboard' : 'dashboard';
    $badgeText = fn (int $count): string => $count > 99 ? '99+' : \App\Support\Digits::localize($count);
    $canAdmin = $user?->can(\App\Enums\StaffPermission::AccessAdmin->value) === true;
@endphp

<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[70] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-brand-800 focus:shadow">{{ __('nav.skip_to_content') }}</a>

<div class="lg:flex lg:gap-3 lg:p-3">
    {{-- Side rail (desktop) and bottom dock (phones) --}}
    <nav
        aria-label="{{ $isAdmin ? __('nav.admin_menu') : __('nav.member_menu') }}"
        class="glass fixed inset-x-2.5 bottom-[calc(0.625rem+env(safe-area-inset-bottom))] z-50 flex items-stretch justify-around gap-1 rounded-3xl p-1.5 lg:sticky lg:inset-x-auto lg:bottom-auto lg:top-3 lg:h-[calc(100dvh-1.5rem)] lg:w-20 lg:shrink-0 lg:flex-col lg:justify-start lg:gap-1.5 lg:self-start lg:overflow-y-auto lg:py-3"
    >
        @if (Route::has($homeRoute))
            <a href="{{ route($homeRoute) }}" class="mb-2 hidden size-12 shrink-0 place-items-center self-center rounded-2xl hover:bg-white/70 lg:grid" aria-label="{{ config('app.name') }}">
                <svg viewBox="0 0 40 40" class="size-9" fill="none" aria-hidden="true">
                    <circle cx="15" cy="20" r="10" class="stroke-brand-700" stroke-width="3" />
                    <circle cx="25" cy="20" r="10" class="stroke-gold-500" stroke-width="3" />
                </svg>
            </a>
        @endif

        @foreach ($sections as $section)
            <a
                href="{{ $section['href'] }}"
                @if ($section['active']) aria-current="page" @endif
                @class([
                    'relative flex min-h-14 min-w-0 flex-1 flex-col items-center justify-center gap-0.5 rounded-2xl px-1 text-center text-xs font-medium transition lg:min-h-16 lg:flex-none',
                    'bg-brand-50 text-brand-800 shadow-sm ring-1 ring-brand-600/15' => $section['active'],
                    'text-stone-700 hover:bg-white/70' => ! $section['active'],
                ])
            >
                @if ($section['active'])
                    <span class="absolute -start-1.5 top-1/2 hidden h-6 w-1 -translate-y-1/2 rounded-full bg-brand-600 lg:block" aria-hidden="true"></span>
                @endif

                <x-icon :name="$section['icon']" />
                <span class="w-full leading-snug">{{ __($section['label']) }}</span>

                @if ($section['badge'] > 0)
                    <span class="absolute end-0 top-0.5 min-w-5 rounded-full bg-gold-700 px-1 text-center text-xs font-bold leading-5 text-white">
                        <span aria-hidden="true">{{ $badgeText($section['badge']) }}</span>
                        <span class="sr-only">{{ __('admin.waiting.badge_sr', ['count' => $section['badge']]) }}</span>
                    </span>
                @endif
            </a>
        @endforeach
    </nav>

    <div class="flex min-w-0 flex-1 flex-col lg:gap-3">
        {{-- Top bar --}}
        <header class="glass sticky top-2.5 z-40 mx-2.5 mt-2.5 flex items-center gap-1 rounded-3xl px-3 py-2 lg:top-3 lg:mx-0 lg:mt-0 lg:px-4">
            <div class="flex min-w-0 flex-1 items-center gap-2">
                @if (Route::has($homeRoute))
                    <a href="{{ route($homeRoute) }}" class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl lg:hidden" aria-label="{{ config('app.name') }}">
                        <svg viewBox="0 0 40 40" class="size-8" fill="none" aria-hidden="true">
                            <circle cx="15" cy="20" r="10" class="stroke-brand-700" stroke-width="3" />
                            <circle cx="25" cy="20" r="10" class="stroke-gold-500" stroke-width="3" />
                        </svg>
                    </a>
                @endif
                <p class="truncate text-lg font-bold text-brand-800">{{ filled($title) ? $title : config('app.name') }}</p>
                @if ($isAdmin)
                    <x-badge variant="gold" class="hidden shrink-0 sm:inline-flex">{{ __('nav.admin') }}</x-badge>
                @endif
            </div>

            <x-language-switcher class="hidden sm:inline-flex" />
            @if ($isAdmin)
                <x-theme-toggle />
            @endif
            <x-notification-bell />

            <details class="relative">
                <summary
                    class="flex size-11 cursor-pointer list-none items-center justify-center rounded-full bg-brand-100 font-semibold text-brand-800 hover:bg-brand-50 [&::-webkit-details-marker]:hidden"
                    aria-label="{{ __('panel.account_menu') }}"
                >{{ $initial !== '' ? $initial : '•' }}</summary>

                <div class="glass-strong absolute end-0 top-full z-50 mt-2 w-64 rounded-2xl p-2">
                    @if ($user !== null)
                        <p class="truncate px-3 py-2 text-sm font-medium text-stone-600">{{ $user->name }}</p>
                    @endif

                    <div class="px-1 py-1 sm:hidden">
                        <x-language-switcher />
                    </div>

                    @if ($isAdmin && Route::has('dashboard'))
                        <a href="{{ route('dashboard') }}" class="flex min-h-11 items-center gap-2 rounded-xl px-3 font-medium text-stone-800 hover:bg-white/70">
                            <x-icon name="swap" class="size-5" />{{ __('panel.member_area') }}
                        </a>
                    @endif

                    @if (! $isAdmin && $canAdmin && Route::has('admin.dashboard'))
                        <a href="{{ route('admin.dashboard') }}" class="flex min-h-11 items-center gap-2 rounded-xl px-3 font-medium text-stone-800 hover:bg-white/70">
                            <x-icon name="swap" class="size-5" />{{ __('panel.admin_area') }}
                        </a>
                    @endif

                    @if (! $isAdmin && Route::has('billing.index'))
                        <a href="{{ route('billing.index') }}" class="flex min-h-11 items-center gap-2 rounded-xl px-3 font-medium text-stone-800 hover:bg-white/70">
                            <x-icon name="card" class="size-5" />{{ __('billing.nav.billing') }}
                        </a>
                    @endif

                    @if (! $isAdmin && Route::has('plans.index'))
                        <a href="{{ route('plans.index') }}" class="flex min-h-11 items-center gap-2 rounded-xl px-3 font-medium text-stone-800 hover:bg-white/70">
                            <x-icon name="tag" class="size-5" />{{ __('billing.nav.plans') }}
                        </a>
                    @endif

                    @if (Route::has('logout'))
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex min-h-11 w-full items-center gap-2 rounded-xl px-3 text-start font-medium text-red-800 hover:bg-red-50">
                                <x-icon name="logout" class="size-5" />{{ __('nav.logout') }}
                            </button>
                        </form>
                    @endif
                </div>
            </details>
        </header>

        <div class="flex min-w-0 flex-1 flex-col lg:flex-row lg:items-start lg:gap-3">
            @if (count($pages) > 1)
                {{-- Second menu: chips on phones --}}
                <nav aria-label="{{ __('panel.section_pages') }}" class="glass mx-2.5 mt-2.5 flex gap-1 overflow-x-auto rounded-2xl p-1.5 lg:hidden">
                    @foreach ($pages as $page)
                        <a
                            href="{{ $page['href'] }}"
                            @if ($page['active']) aria-current="page" @endif
                            @class([
                                'inline-flex min-h-11 shrink-0 items-center gap-2 rounded-full px-4 text-sm font-semibold',
                                'bg-brand-700 text-white' => $page['active'],
                                'text-stone-800 hover:bg-white/70' => ! $page['active'],
                            ])
                        >
                            {{ __($page['label']) }}
                            @if ($page['badge'] > 0)
                                <span class="min-w-5 rounded-full bg-gold-700 px-1 text-center text-xs font-bold leading-5 text-white"><span aria-hidden="true">{{ $badgeText($page['badge']) }}</span><span class="sr-only">{{ __('admin.waiting.badge_sr', ['count' => $page['badge']]) }}</span></span>
                            @endif
                        </a>
                    @endforeach
                </nav>

                {{-- Second menu: column on desktop --}}
                <nav aria-label="{{ __('panel.section_pages') }}" class="glass sticky top-[5.25rem] hidden w-56 shrink-0 rounded-3xl p-2 lg:block">
                    <p class="px-3 pb-1 pt-2 text-sm font-bold text-stone-600">{{ __($current['title']) }}</p>
                    <ul class="space-y-1">
                        @foreach ($pages as $page)
                            <li>
                                <a
                                    href="{{ $page['href'] }}"
                                    @if ($page['active']) aria-current="page" @endif
                                    @class([
                                        'flex min-h-11 items-center justify-between gap-2 rounded-xl px-3 font-medium',
                                        'bg-brand-50 text-brand-800 ring-1 ring-brand-600/15' => $page['active'],
                                        'text-stone-800 hover:bg-white/70' => ! $page['active'],
                                    ])
                                >
                                    {{ __($page['label']) }}
                                    @if ($page['badge'] > 0)
                                        <span class="min-w-5 rounded-full bg-gold-700 px-1 text-center text-xs font-bold leading-5 text-white"><span aria-hidden="true">{{ $badgeText($page['badge']) }}</span><span class="sr-only">{{ __('admin.waiting.badge_sr', ['count' => $page['badge']]) }}</span></span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endif

            <main id="main" class="min-w-0 flex-1 px-2.5 pb-28 pt-3 lg:px-0 lg:pb-8 lg:pt-0">
                @if (session('status'))
                    <x-alert type="success" class="mb-4">{{ session('status') }}</x-alert>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</div>
