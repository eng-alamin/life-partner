<div class="mt-4">
    @if ($notice !== '')
        <x-alert type="success" class="mb-2">{{ $notice }}</x-alert>
    @endif

    @if ($alreadyReported)
        <p class="text-sm text-stone-600">{{ __('reports.ui.already_reported') }}</p>
    @else
        @error('form')
            <x-alert type="danger" class="mb-2">{{ $message }}</x-alert>
        @enderror

        @if (! $open)
            <x-button type="button" variant="ghost" size="sm" wire:click="toggle">{{ __('reports.ui.open_button') }}</x-button>
            <p class="mt-1 text-sm text-stone-600">{{ __('reports.ui.help') }}</p>
        @else
            <x-card>
                <h2 class="text-lg font-semibold text-stone-900">{{ __('reports.ui.heading') }}</h2>
                <p class="mt-1 text-stone-700">{{ __('reports.ui.intro') }}</p>

                <form wire:submit="submit" class="mt-3 space-y-3">
                    <x-field name="reason" :label="__('reports.ui.reason_label')" required>
                        <x-select name="reason" wire:model="reason">
                            <option value="">{{ __('reports.ui.choose_reason') }}</option>
                            @foreach ($reasons as $case)
                                <option value="{{ $case->value }}">{{ $case->label() }}</option>
                            @endforeach
                        </x-select>
                    </x-field>

                    <x-field name="details" :label="__('reports.ui.details_label')" :hint="__('reports.ui.details_hint', ['max' => \App\Support\Digits::localize($detailsMax)])">
                        <x-textarea name="details" rows="4" maxlength="{{ $detailsMax }}" wire:model="details" />
                    </x-field>

                    <div class="flex flex-wrap gap-2">
                        <x-button type="submit" wire:loading.attr="disabled">{{ __('reports.ui.submit') }}</x-button>
                        <x-button type="button" variant="secondary" wire:click="toggle">{{ __('reports.ui.cancel') }}</x-button>
                    </div>
                </form>
            </x-card>
        @endif
    @endif
</div>
