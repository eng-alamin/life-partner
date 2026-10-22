@php
    use App\Support\Digits;
@endphp

<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-brand-800">{{ __('admin_plans.list.heading') }}</h1>
        <x-button :href="route('admin.plans.create')" size="sm">{{ __('admin_plans.list.new_button') }}</x-button>
    </div>
    <p class="mt-1 text-stone-600">{{ __('admin_plans.list.intro') }}</p>

    @error('form')
        <x-alert type="danger" class="mt-4">{{ $message }}</x-alert>
    @enderror

    @if ($notice)
        <x-alert type="success" class="mt-4">{{ $notice }}</x-alert>
    @endif

    <div class="mt-4">
        @if ($rows === [])
            <x-empty-state :title="__('admin_plans.list.empty_title')" :text="__('admin_plans.list.empty_text')" />
        @else
            <ul class="space-y-3">
                @foreach ($rows as $row)
                    <li wire:key="plan-{{ $row['code'] }}">
                        <x-card variant="small" padding="p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="min-w-0 space-y-1">
                                    <p class="text-lg font-semibold text-stone-900">{{ $row['name'] }} · {{ $row['price'] }}</p>
                                    <p class="flex flex-wrap items-center gap-2 text-sm text-stone-600">
                                        <x-badge :variant="$row['active'] ? 'brand' : 'stone'">{{ $row['active'] ? __('admin_plans.list.active') : __('admin_plans.list.inactive') }}</x-badge>
                                        <span>{{ $row['kind']->label() }}</span>
                                        <span class="font-mono" dir="ltr">{{ $row['code'] }}</span>
                                    </p>
                                    <p class="text-sm text-stone-700">
                                        @if ($row['credits'] > 0)
                                            {{ __('admin_plans.list.credits', ['count' => Digits::localize($row['credits'])]) }}
                                        @endif
                                        @if ($row['days'])
                                            · {{ __('admin_plans.list.days', ['count' => Digits::localize($row['days'])]) }}
                                            · {{ $row['limit'] ? __('admin_plans.list.limit', ['count' => Digits::localize($row['limit'])]) : __('admin_plans.list.no_limit') }}
                                        @endif
                                        · {{ __('admin_plans.list.sort') }}: {{ $row['sort'] }}
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <x-button :href="route('admin.plans.edit', $row['code'])" variant="secondary" size="sm">{{ __('admin_plans.list.edit') }}</x-button>
                                    @if ($row['active'])
                                        <x-button type="button" variant="secondary" size="sm" wire:click="setActive('{{ $row['code'] }}', false)" wire:confirm="{{ __('admin_plans.list.deactivate_confirm') }}" wire:loading.attr="disabled">{{ __('admin_plans.list.deactivate') }}</x-button>
                                    @else
                                        <x-button type="button" size="sm" wire:click="setActive('{{ $row['code'] }}', true)" wire:loading.attr="disabled">{{ __('admin_plans.list.activate') }}</x-button>
                                    @endif
                                </div>
                            </div>
                        </x-card>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
