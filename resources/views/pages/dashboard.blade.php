<x-layouts::app :title="__('account.dashboard.title')">
    <h1 class="text-2xl font-bold text-brand-800">{{ __('account.dashboard.heading', ['name' => auth()->user()->name]) }}</h1>
    <p class="mt-2 text-stone-600">{{ __('account.dashboard.intro') }}</p>

    @php
        $actions = [
            ['biodatas.index', 'search', 'nav.search'],
            ['interests.index', 'mail', 'nav.interests'],
            ['shortlist.index', 'bookmark', 'nav.shortlist'],
            ['my-biodata', 'id', 'nav.my_biodata'],
            ['plans.index', 'tag', 'billing.nav.plans'],
            ['billing.index', 'card', 'billing.nav.billing'],
        ];
    @endphp

    <h2 class="mt-6 text-xl font-bold text-brand-800">{{ __('panel.quick_actions') }}</h2>

    <ul class="mt-3 grid grid-cols-2 gap-3 lg:grid-cols-3">
        @foreach ($actions as [$route, $icon, $label])
            @if (Route::has($route))
                <li>
                    <a href="{{ route($route) }}" class="glass-sm flex h-full min-h-28 flex-col justify-between gap-3 p-4 font-semibold text-stone-900 transition hover:-translate-y-0.5 hover:shadow-lg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 motion-reduce:hover:translate-y-0">
                        <span class="icon-chip"><x-icon :name="$icon" /></span>
                        <span>{{ __($label) }}</span>
                    </a>
                </li>
            @endif
        @endforeach
    </ul>

    @can(\App\Enums\StaffPermission::AccessAdmin->value)
        <div class="mt-6">
            <x-button :href="route('admin.dashboard')" variant="gold">{{ __('nav.admin') }}</x-button>
        </div>
    @endcan
</x-layouts::app>
