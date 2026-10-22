<div>
    <a href="{{ route('admin.approvals') }}" class="inline-flex min-h-11 items-center font-medium text-brand-700 hover:underline">
        &larr; {{ __('admin.review.back') }}
    </a>

    <div class="mt-2 flex flex-wrap items-center gap-3">
        <h1 class="font-mono text-2xl font-bold text-brand-800">{{ $biodata->public_code }}</h1>
        <x-badge :variant="$biodata->status->badge()">{{ $biodata->status->label() }}</x-badge>
    </div>
    <p class="mt-1 text-stone-600">{{ __('admin.review.intro') }}</p>

    @error('form')
        <x-alert type="danger" class="mt-4">{{ $message }}</x-alert>
    @enderror

    @if ($isStale && $canDecide)
        <x-alert type="warning" class="mt-4">
            <p>{{ __('admin.review.stale') }}</p>
            <x-button variant="secondary" size="sm" class="mt-3" wire:click="reload">{{ __('admin.review.reload') }}</x-button>
        </x-alert>
    @endif

    @unless ($canDecide)
        <x-alert type="info" class="mt-4">{{ __('admin.review.not_pending') }}</x-alert>
    @endunless

    <div class="mt-4 grid gap-4 lg:grid-cols-2">
        @foreach ($sections as $section)
            <x-card variant="small" padding="p-4" wire:key="section-{{ $loop->index }}">
                <h2 class="text-lg font-semibold text-stone-900">{{ $section['title'] }}</h2>
                <dl class="mt-3 space-y-2">
                    @foreach ($section['rows'] as $row)
                        <div class="grid gap-0.5 sm:grid-cols-3 sm:gap-3">
                            <dt class="text-sm font-medium text-stone-600 sm:col-span-1">{{ $row[0] }}</dt>
                            <dd class="min-w-0 break-words text-stone-900 sm:col-span-2 {{ ($section['long'] ?? false) ? 'whitespace-pre-line' : '' }}">{{ $row[1] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-card>
        @endforeach
    </div>

    @if ($canDecide)
        <x-card class="mt-6" padding="p-4 sm:p-5">
            <h2 class="text-lg font-semibold text-stone-900">{{ __('admin.review.decision') }}</h2>

            <div class="mt-3">
                <x-button wire:click="approve" :disabled="$isStale" wire:loading.attr="disabled" wire:target="approve,reject">
                    {{ __('admin.review.approve') }}
                </x-button>
            </div>

            <form wire:submit="reject" class="mt-5 space-y-3" novalidate>
                <x-field name="rejectReason" :label="__('admin.review.reason_label')" :hint="__('admin.review.reason_hint')" :required="true">
                    <x-textarea name="rejectReason" wire:model="rejectReason" rows="3" maxlength="500" />
                </x-field>

                <x-button type="submit" variant="danger" :disabled="$isStale" wire:loading.attr="disabled" wire:target="approve,reject">
                    {{ __('admin.review.reject') }}
                </x-button>
            </form>
        </x-card>
    @endif
</div>
