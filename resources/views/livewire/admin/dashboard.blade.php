<div>
    <h1 class="text-2xl font-bold text-brand-800">{{ __('admin.dashboard.heading') }}</h1>
    <p class="mt-1 text-stone-600">{{ __('admin.dashboard.intro') }}</p>

    <dl class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-5">
        @foreach ([
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'drafts' => $drafts,
            'members' => $members,
        ] as $key => $value)
            <x-stat-card :label="__('admin.dashboard.stats.'.$key)" :value="$value" />
        @endforeach
    </dl>

    <section class="mt-8" aria-labelledby="waiting-heading">
        <h2 id="waiting-heading" class="text-xl font-bold text-brand-800">{{ __('admin.waiting.heading') }}</h2>
        <p class="mt-1 text-stone-600">{{ __('admin.waiting.intro') }}</p>

        @if (array_sum($waiting) === 0)
            <p class="mt-3 text-stone-700">{{ __('admin.waiting.none') }}</p>
        @endif

        <ul class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($waiting as $key => $count)
                <li>
                    <a href="{{ route($waitingRoutes[$key], $key === 'payments_attention' ? ['tab' => 'attention'] : []) }}" class="block rounded-2xl transition hover:-translate-y-0.5 hover:shadow-lg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 motion-reduce:hover:translate-y-0">
                        <x-card variant="small" padding="p-4">
                            <span class="block text-sm font-medium text-stone-600">{{ __('admin.waiting.items.'.$key) }}</span>
                            <span class="mt-1 block text-3xl font-bold text-brand-800">{{ $count }}</span>
                            <span class="mt-2 block text-sm font-medium text-brand-700">{{ __('admin.waiting.open') }}</span>
                        </x-card>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

    <div class="mt-6 flex flex-wrap items-center gap-3">
        @can(\App\Enums\StaffPermission::ReviewBiodata->value)
            <x-button :href="route('admin.approvals')">{{ __('admin.dashboard.open_queue') }}</x-button>
        @endcan

        @if ($canHandleReports)
            <x-button :href="route('admin.reports')" variant="secondary">
                {{ __('admin_reports.dashboard.open_reports', ['count' => $openReports]) }}
            </x-button>
        @endif
    </div>
</div>
