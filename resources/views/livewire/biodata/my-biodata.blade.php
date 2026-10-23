<div>
    <h1 class="text-2xl font-bold text-brand-800">{{ __('overview.heading') }}</h1>

    @error('form')
        <x-alert type="danger" class="mt-4">{{ $message }}</x-alert>
    @enderror

    @if ($biodata === null)
        <x-empty-state class="mt-4" :title="__('overview.empty_title')" :text="__('overview.empty_text')">
            @if ($canEdit)
                <x-button :href="route('my-biodata.edit')" size="lg">{{ __('overview.start') }}</x-button>
            @endif
        </x-empty-state>
    @else
        <x-card class="mt-4">
            <div class="flex flex-wrap items-center gap-3">
                <x-badge :variant="$status->badge()">{{ $status->label() }}</x-badge>
                <p class="text-stone-700">
                    {{ __('overview.code') }}:
                    <span class="font-mono font-semibold text-stone-900">{{ $biodata->public_code }}</span>
                </p>
            </div>

            <p class="mt-3 text-stone-700">{{ __('overview.status_text.'.$status->value) }}</p>

            @if ($status === \App\Enums\BiodataStatus::Rejected && filled($biodata->rejection_reason))
                <x-alert type="danger" class="mt-3">
                    {{ __('overview.rejection_reason') }}
                    <span class="mt-1 block font-semibold">{{ $biodata->rejection_reason }}</span>
                </x-alert>
            @endif

            <div class="mt-5">
                <div class="flex items-center justify-between gap-3 text-sm font-medium text-stone-700">
                    <span id="completion-label">{{ __('overview.completion') }}</span>
                    <span>{{ $percent }}%</span>
                </div>
                <div class="mt-1.5 h-3 overflow-hidden rounded-full bg-stone-200" role="progressbar" aria-labelledby="completion-label" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percent }}">
                    <div class="h-full rounded-full bg-brand-600" style="width: {{ $percent }}%"></div>
                </div>
            </div>

            <ul class="mt-5 grid gap-2 sm:grid-cols-2">
                @foreach ($steps as $step)
                    <li class="flex min-h-11 items-center gap-2 text-stone-800">
                        @if ($step['done'])
                            <span class="inline-flex size-6 items-center justify-center rounded-full bg-brand-100 text-brand-800" aria-hidden="true">&#10003;</span>
                            <span class="sr-only">{{ __('overview.done') }}:</span>
                        @else
                            <span class="inline-flex size-6 items-center justify-center rounded-full border border-stone-400 text-stone-500" aria-hidden="true">&#9675;</span>
                            <span class="sr-only">{{ __('overview.todo') }}:</span>
                        @endif
                        {{ $step['label'] }}
                    </li>
                @endforeach
            </ul>

            @if ($isDeactivated)
                <x-alert type="warning" class="mt-5">{{ __('biodata.notice.deactivated') }}</x-alert>
            @endif

            @if ($hiddenByStaff)
                <x-alert type="warning" class="mt-5">{{ __('visibility.staff_hidden') }}</x-alert>
            @elseif ($canUnhide)
                <p class="mt-5 text-stone-700">
                    {{ $unhideNeedsReview ? __('visibility.unhide.help_changed') : __('visibility.unhide.help_same') }}
                </p>
            @elseif ($canReactivate)
                <p class="mt-5 text-stone-700">
                    {{ $unhideNeedsReview ? __('visibility.reactivate.help_changed') : __('visibility.reactivate.help_same') }}
                </p>
            @elseif ($canHide)
                <p class="mt-5 text-stone-700">{{ __('visibility.hide.help') }}</p>
            @endif

            @if ($confirmingDeactivate && $canDeactivate)
                <div class="mt-5 rounded-xl border border-stone-300 bg-white/60 p-4">
                    <p class="text-stone-800">{{ __('visibility.deactivate.help') }}</p>

                    <label for="f-reason" class="mt-3 block font-medium text-stone-800">{{ __('visibility.deactivate.reason_label') }}</label>
                    <x-select name="reason" wire:model="reason" class="mt-1">
                        <option value="">{{ __('visibility.deactivate.reason_none') }}</option>
                        @foreach ($reasons as $option)
                            <option value="{{ $option->value }}">{{ $option->label() }}</option>
                        @endforeach
                    </x-select>
                    @error('reason')
                        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                    @enderror

                    <div class="mt-4 flex flex-wrap gap-3">
                        <x-button wire:click="deactivate" wire:loading.attr="disabled" wire:target="deactivate">
                            {{ __('visibility.deactivate.confirm') }}
                        </x-button>
                        <x-button variant="secondary" wire:click="cancelDeactivate">
                            {{ __('visibility.deactivate.cancel') }}
                        </x-button>
                    </div>
                </div>
            @endif

            <div class="mt-6 flex flex-wrap gap-3">
                @if ($canEdit)
                    <x-button :href="route('my-biodata.edit')">
                        {{ $allDone ? __('overview.edit') : __('overview.continue') }}
                    </x-button>
                @endif

                @if ($canSubmit)
                    <x-button variant="gold" wire:click="submit" wire:loading.attr="disabled" wire:target="submit">
                        {{ __('biodata.submit.button') }}
                    </x-button>
                @endif

                @if ($canHide)
                    <x-button variant="secondary" wire:click="hide" wire:loading.attr="disabled" wire:target="hide">
                        {{ __('visibility.hide.button') }}
                    </x-button>
                @endif

                @if ($canUnhide)
                    <x-button variant="gold" wire:click="unhide" wire:loading.attr="disabled" wire:target="unhide">
                        {{ __('visibility.unhide.button') }}
                    </x-button>
                @endif

                @if ($canReactivate)
                    <x-button variant="gold" wire:click="reactivate" wire:loading.attr="disabled" wire:target="reactivate">
                        {{ __('visibility.reactivate.button') }}
                    </x-button>
                @endif

                @if ($canDeactivate && ! $confirmingDeactivate)
                    <x-button variant="secondary" wire:click="startDeactivate">
                        {{ __('visibility.deactivate.button') }}
                    </x-button>
                @endif
            </div>
        </x-card>

        @if ($canManagePhotos)
            <livewire:biodata.my-photos />
        @endif
    @endif

    <p class="mt-8 text-sm">
        <a href="{{ route('account.verification') }}" class="inline-flex min-h-11 items-center font-semibold text-brand-700 underline underline-offset-2">{{ __('identity.link') }}</a>
    </p>

    <p class="text-sm">
        <a href="{{ route('account.export') }}" class="inline-flex min-h-11 items-center font-semibold text-brand-700 underline underline-offset-2">{{ __('account_export.link') }}</a>
    </p>

    <p class="text-sm">
        <a href="{{ route('account.delete') }}" class="inline-flex min-h-11 items-center text-red-800 underline underline-offset-2 hover:text-red-900">{{ __('account_deletion.link') }}</a>
    </p>
</div>
