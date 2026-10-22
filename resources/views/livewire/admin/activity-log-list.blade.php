<div>
    <h1 class="text-2xl font-bold text-brand-800">{{ __('admin_activity.heading') }}</h1>
    <p class="mt-1 text-stone-600">{{ __('admin_activity.intro') }}</p>

    <x-card class="mt-4" padding="p-4">
        <div class="grid gap-3 sm:grid-cols-3">
            <x-field name="log" :label="__('admin_activity.filter_label')">
                <x-select name="log" wire:model.live="log">
                    <option value="">{{ __('admin_activity.all') }}</option>
                    @foreach ($logs as $name)
                        <option value="{{ $name }}">{{ __('admin_activity.logs.'.$name) }}</option>
                    @endforeach
                </x-select>
            </x-field>
        </div>
    </x-card>

    <div class="mt-4">
        @if ($rows === [])
            <x-empty-state :title="__('admin_activity.empty_title')" :text="__('admin_activity.empty_text')" />
        @else
            <ul class="space-y-3">
                @foreach ($rows as $row)
                    <li wire:key="activity-{{ $row['id'] }}">
                        <x-card variant="small" padding="p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="font-semibold text-stone-900">{{ $row['event'] }}</p>
                                <x-badge variant="brand">{{ \Illuminate\Support\Facades\Lang::has('admin_activity.logs.'.$row['log']) ? __('admin_activity.logs.'.$row['log']) : $row['log'] }}</x-badge>
                            </div>

                            <p class="mt-1 text-sm text-stone-600">
                                {{ $row['time']?->translatedFormat('j M Y, H:i') }}
                                · {{ __('admin_activity.by') }}
                                <span class="font-medium text-stone-800">{{ $row['causer']['label'] }}</span>
                                @if ($row['subject'])
                                    · <span class="font-mono">{{ $row['subject'] }}</span>
                                @endif
                            </p>

                            @if ($row['details'] !== [])
                                <dl class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm">
                                    @foreach ($row['details'] as $key => $value)
                                        <div class="flex gap-1">
                                            <dt class="text-stone-600">{{ $key }}:</dt>
                                            <dd class="break-all font-mono text-stone-900">{{ $value }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            @endif
                        </x-card>
                    </li>
                @endforeach
            </ul>

            <div class="mt-4 flex items-center justify-between gap-3">
                <x-button variant="secondary" size="sm" wire:click="previousPage" :disabled="$activities->onFirstPage()">
                    {{ __('admin_activity.previous') }}
                </x-button>
                <x-button variant="secondary" size="sm" wire:click="nextPage" :disabled="! $activities->hasMorePages()">
                    {{ __('admin_activity.next') }}
                </x-button>
            </div>
        @endif
    </div>
</div>
