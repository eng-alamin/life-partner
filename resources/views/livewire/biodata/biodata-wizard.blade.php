<div>
    <h1 class="text-2xl font-bold text-brand-800">{{ __('biodata.heading') }}</h1>
    <p class="mt-1 text-stone-600">{{ __('biodata.intro') }}</p>

    @error('form')
        <x-alert type="danger" class="mt-4">{{ $message }}</x-alert>
    @enderror

    @if ($notice !== '')
        <x-alert type="warning" class="mt-4">{{ $notice }}</x-alert>
    @endif

    @if ($readOnly)
        <x-alert type="warning" class="mt-4">
            @if ($status === \App\Enums\BiodataStatus::Deactivated)
                {{ __('biodata.notice.deactivated') }}
            @else
                {{ __('biodata.notice.read_only') }}
            @endif
        </x-alert>
    @elseif ($status === \App\Enums\BiodataStatus::Rejected)
        <x-alert type="danger" class="mt-4">
            {{ __('biodata.notice.rejected') }}
            @if ($rejectionReason !== '')
                <span class="mt-1 block font-semibold">{{ $rejectionReason }}</span>
            @endif
        </x-alert>
    @elseif ($status === \App\Enums\BiodataStatus::Pending)
        <x-alert type="info" class="mt-4">{{ __('biodata.notice.pending') }}</x-alert>
    @elseif ($status === \App\Enums\BiodataStatus::Approved)
        <x-alert type="info" class="mt-4">{{ __('biodata.notice.approved_edit') }}</x-alert>
    @endif

    <x-stepper class="mt-4" :steps="$stepLabels" :current="$step" />

    <form wire:submit="next" class="mt-4" novalidate>
        <x-card>
            <fieldset @disabled($readOnly) class="space-y-4">
                <legend class="mb-2 text-xl font-semibold text-stone-900">{{ $stepLabels[$step - 1] }}</legend>

                @include('biodata.steps.'.$stepKey)
            </fieldset>
        </x-card>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            @if ($step > 1)
                <x-button type="button" variant="secondary" wire:click="back">{{ __('biodata.back') }}</x-button>
            @else
                <span></span>
            @endif

            @unless ($readOnly)
                <x-button type="submit" size="lg" wire:loading.attr="disabled" wire:target="next">
                    {{ $step < count($stepLabels) ? __('biodata.next') : __('biodata.save_finish') }}
                </x-button>
            @endunless
        </div>
    </form>

    @if ($canSubmit)
        <x-card variant="mint" class="mt-4">
            <h2 class="text-xl font-semibold text-brand-800">{{ __('biodata.submit.heading') }}</h2>
            <p class="mt-1 text-stone-700">{{ __('biodata.submit.text') }}</p>
            <x-button type="button" size="lg" class="mt-4" wire:click="submit" wire:loading.attr="disabled" wire:target="submit">
                {{ __('biodata.submit.button') }}
            </x-button>
        </x-card>
    @endif
</div>
