<x-layouts::app :title="__('account.dashboard.title')">
    <h1 class="text-2xl font-bold text-brand-800">{{ __('account.dashboard.heading', ['name' => auth()->user()->name]) }}</h1>
    <p class="mt-2 text-stone-600">{{ __('account.dashboard.intro') }}</p>

    <div class="mt-4 flex flex-wrap gap-2">
        <x-button :href="route('plans.index')" variant="secondary">{{ __('billing.nav.plans') }}</x-button>
        <x-button :href="route('billing.index')" variant="secondary">{{ __('billing.nav.billing') }}</x-button>

        @can(\App\Enums\StaffPermission::AccessAdmin->value)
            <x-button :href="route('admin.dashboard')" variant="gold">{{ __('nav.admin') }}</x-button>
        @endcan
    </div>
</x-layouts::app>
