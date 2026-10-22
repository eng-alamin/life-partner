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
            'admin.dashboard' => 'nav.dashboard',
            'admin.approvals' => 'nav.approvals',
            'admin.hidden' => 'nav.hidden',
            'admin.photos' => 'nav.photos',
            'admin.users' => 'nav.users',
            'admin.deletions' => 'nav.deletions',
            'admin.verification' => 'nav.verification',
            'admin.reports' => 'nav.reports',
            'admin.plans' => 'nav.plans',
            'admin.payments' => 'nav.payments',
            'admin.content' => 'nav.content',
            'admin.activity-log' => 'nav.activity_log',
            'admin.settings' => 'nav.admin_settings',
        ];

        // Small numbers next to the queues this person can work on.
        $waiting = auth()->user() ? app(\App\Services\Admin\WaitingCounts::class)->for(auth()->user()) : [];
        $badgeKeys = array_flip(\App\Services\Admin\WaitingCounts::ROUTES);

        // Only an admin with payments.manage sees the payments link.
        if (! auth()->user()?->can(\App\Enums\StaffPermission::ManagePayments->value)) {
            unset($menu['admin.payments']);
        }

        // Only an admin with pages.manage sees the content link.
        if (! auth()->user()?->can(\App\Enums\StaffPermission::ManagePages->value)) {
            unset($menu['admin.content']);
        }

        // Only an admin with users.manage sees the users, deletions and activity log links.
        if (! auth()->user()?->can(\App\Enums\StaffPermission::ManageUsers->value)) {
            unset($menu['admin.users'], $menu['admin.deletions'], $menu['admin.activity-log']);
        }

        // Only an admin with settings.manage sees the settings link.
        if (! auth()->user()?->can(\App\Enums\StaffPermission::ManageSettings->value)) {
            unset($menu['admin.settings']);
        }

        // Only an admin with plans.manage sees the plans link.
        if (! auth()->user()?->can(\App\Enums\StaffPermission::ManagePlans->value)) {
            unset($menu['admin.plans']);
        }
    @endphp

    <header class="sticky top-0 z-40 glass-bar">
        <div class="flex h-16 items-center justify-between gap-3 px-4 sm:px-6">
            <div class="flex items-center gap-3">
                <x-logo :href="Route::has('admin.dashboard') ? route('admin.dashboard') : route('home')" />
                <x-badge variant="gold">{{ __('nav.admin') }}</x-badge>
            </div>
            <div class="flex items-center gap-2">
                <x-language-switcher />
                <x-notification-bell />
                @if (Route::has('logout'))
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-button type="submit" variant="ghost" size="sm">{{ __('nav.logout') }}</x-button>
                    </form>
                @endif
            </div>
        </div>

        <details class="border-t border-white/70 lg:hidden">
            <summary class="flex min-h-11 cursor-pointer list-none items-center px-4 font-medium text-stone-700 [&::-webkit-details-marker]:hidden">{{ __('nav.menu') }}</summary>
            <nav aria-label="{{ __('nav.admin_menu') }}" class="space-y-1 px-3 pb-3">
                @foreach ($menu as $route => $label)
                    <x-nav-link :route="$route" :count="$waiting[$badgeKeys[$route] ?? ''] ?? 0" variant="side">{{ __($label) }}</x-nav-link>
                @endforeach
            </nav>
        </details>
    </header>

    <div class="flex flex-1">
        <nav aria-label="{{ __('nav.admin_menu') }}" class="glass-bar hidden w-60 shrink-0 space-y-1 border-r border-white/70 p-3 lg:sticky lg:top-16 lg:block lg:h-[calc(100dvh-4rem)] lg:overflow-y-auto">
            @foreach ($menu as $route => $label)
                <x-nav-link :route="$route" :count="$waiting[$badgeKeys[$route] ?? ''] ?? 0" variant="side">{{ __($label) }}</x-nav-link>
            @endforeach
        </nav>

        <main id="main" class="min-w-0 flex-1 px-4 pb-10 pt-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <x-alert type="success" class="mb-4">{{ session('status') }}</x-alert>
            @endif

            {{ $slot }}
        </main>
    </div>
</body>
</html>
