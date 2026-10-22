<div>
    <h1 class="text-2xl font-bold text-brand-800">{{ __('admin_pages.list.heading') }}</h1>
    <p class="mt-1 text-stone-600">{{ __('admin_pages.list.intro') }}</p>

    <ul class="mt-4 space-y-3">
        @foreach ($rows as $row)
            <li wire:key="page-{{ $row['slug'] }}">
                <x-card variant="small" padding="p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="min-w-0 space-y-1">
                            <p class="text-lg font-semibold text-stone-900">{{ $row['label'] }}</p>
                            <p class="flex flex-wrap items-center gap-2 text-sm text-stone-600">
                                <x-badge :variant="$row['published'] ? 'brand' : 'stone'">{{ $row['published'] ? __('admin_pages.list.published') : __('admin_pages.list.unpublished') }}</x-badge>
                                <span class="font-mono" dir="ltr">/p/{{ $row['slug'] }}</span>
                                @if ($row['saved'])
                                    <span>{{ __('admin_pages.list.updated', ['when' => $row['updated']]) }}</span>
                                @else
                                    <span>{{ __('admin_pages.list.not_saved') }}</span>
                                @endif
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @if ($row['published'])
                                <x-button :href="route('pages.'.$row['slug'])" variant="secondary" size="sm">{{ __('admin_pages.list.view') }}</x-button>
                            @endif
                            <x-button :href="route('admin.content.edit', $row['slug'])" size="sm">{{ __('admin_pages.list.edit') }}</x-button>
                        </div>
                    </div>
                </x-card>
            </li>
        @endforeach
    </ul>
</div>
