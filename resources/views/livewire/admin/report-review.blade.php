<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-brand-800">{{ __('admin_reports.review.title') }}</h1>
        <x-button :href="route('admin.reports')" variant="secondary" size="sm">{{ __('admin_reports.review.back') }}</x-button>
    </div>
    <p class="mt-1 text-stone-600">{{ __('admin_reports.review.intro') }}</p>

    <x-card class="mt-4">
        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_reports.review.reported_code') }}</dt>
                <dd class="font-mono font-semibold text-stone-900">{{ $report->reported_code }}</dd>
            </div>
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_reports.review.reporter') }}</dt>
                <dd class="font-mono font-semibold text-stone-900">{{ $reporterCode ?? __('admin_reports.review.none') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_reports.review.reason') }}</dt>
                <dd class="font-medium text-stone-900">{{ $report->reason->label() }}</dd>
            </div>
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_reports.review.status') }}</dt>
                <dd><x-badge :variant="$report->status->badge()">{{ $report->status->label() }}</x-badge></dd>
            </div>
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_reports.review.filed') }}</dt>
                <dd class="font-medium text-stone-900">{{ $report->created_at?->format('Y-m-d H:i') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_reports.review.other_reports') }}</dt>
                <dd class="font-medium text-stone-900">{{ __('admin_reports.review.other_reports_value', ['total' => \App\Support\Digits::localize($counts['total']), 'open' => \App\Support\Digits::localize($counts['open'])]) }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-sm text-stone-600">{{ __('admin_reports.review.details') }}</dt>
                <dd class="whitespace-pre-line font-medium text-stone-900">{{ $report->details ?? __('admin_reports.review.details_none') }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-sm text-stone-600">{{ __('admin_reports.review.biodata_status') }}</dt>
                <dd class="flex flex-wrap items-center gap-3">
                    @if ($report->biodata)
                        <x-badge :variant="$report->biodata->status->badge()">{{ $report->biodata->status->label() }}</x-badge>
                        <x-button :href="route('admin.approvals.show', $report->biodata->public_code)" variant="secondary" size="sm">{{ __('admin_reports.review.view_biodata') }}</x-button>
                    @else
                        <span class="font-medium text-stone-900">{{ __('admin_reports.review.biodata_gone') }}</span>
                    @endif
                </dd>
            </div>
            @if (! $isOpen)
                <div class="sm:col-span-2">
                    <dt class="text-sm text-stone-600">{{ __('admin_reports.review.handled') }}</dt>
                    <dd class="font-medium text-stone-900">{{ $report->handled_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm text-stone-600">{{ __('admin_reports.review.resolution') }}</dt>
                    <dd class="whitespace-pre-line font-medium text-stone-900">{{ $report->resolution ?? __('admin_reports.review.none') }}</dd>
                </div>
            @endif
        </dl>
    </x-card>

    @if ($isOpen)
        <x-card class="mt-4">
            <h2 class="text-lg font-semibold text-stone-900">{{ __('admin_reports.review.decision') }}</h2>

            @error('form')
                <x-alert type="danger" class="mt-3">{{ $message }}</x-alert>
            @enderror

            <div class="mt-3 space-y-3">
                <x-field name="resolution" :label="__('admin_reports.review.resolution_label')" :hint="__('admin_reports.review.resolution_hint')">
                    <x-textarea name="resolution" rows="4" maxlength="500" wire:model="resolution" />
                </x-field>

                <div class="flex flex-wrap gap-2">
                    <x-button type="button" variant="secondary" wire:click="dismiss" wire:loading.attr="disabled">{{ __('admin_reports.review.dismiss') }}</x-button>
                    <x-button type="button" wire:click="resolve" wire:loading.attr="disabled">{{ __('admin_reports.review.resolve') }}</x-button>
                    <x-button type="button" variant="ghost" wire:click="hideBiodata" wire:confirm="{{ __('admin_reports.review.hide_confirm') }}" wire:loading.attr="disabled">{{ __('admin_reports.review.hide') }}</x-button>
                </div>
            </div>
        </x-card>
    @else
        <x-alert type="info" class="mt-4">{{ __('admin_reports.review.closed_note') }}</x-alert>
    @endif
</div>
