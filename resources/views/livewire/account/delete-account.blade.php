<div>
    <h1 class="text-2xl font-bold text-brand-800">{{ __('account_deletion.heading') }}</h1>

    @if (session('status'))
        <x-alert type="success" class="mt-4">{{ session('status') }}</x-alert>
    @endif

    @error('form')
        <x-alert type="danger" class="mt-4">{{ $message }}</x-alert>
    @enderror

    @if ($requested)
        <x-card class="mt-4">
            <x-alert type="warning">
                {{ __('account_deletion.requested.text', ['date' => $scheduledOn]) }}
            </x-alert>

            <p class="mt-4 text-stone-700">{{ __('account_deletion.requested.cancel_help') }}</p>

            <div class="mt-5">
                <x-button wire:click="cancelDeletion" wire:loading.attr="disabled" wire:target="cancelDeletion">
                    {{ __('account_deletion.requested.cancel_button') }}
                </x-button>
            </div>
        </x-card>
    @else
        <x-card class="mt-4">
            <p class="text-stone-700">{{ __('account_deletion.intro') }}</p>

            <h2 class="mt-5 text-lg font-semibold text-stone-900">{{ __('account_deletion.what_happens.heading') }}</h2>
            <ul class="mt-2 list-disc space-y-1.5 ps-6 text-stone-700">
                <li>{{ __('account_deletion.what_happens.wait', ['days' => $waitDays]) }}</li>
                <li>{{ __('account_deletion.what_happens.hidden') }}</li>
                <li>{{ __('account_deletion.what_happens.cancel') }}</li>
                <li>{{ __('account_deletion.what_happens.purged') }}</li>
                <li>{{ __('account_deletion.what_happens.payments') }}</li>
            </ul>

            @if ($isStaff)
                <x-alert type="warning" class="mt-5">{{ __('account_deletion.staff') }}</x-alert>
            @elseif (! $confirming)
                <div class="mt-6">
                    <x-button variant="danger" wire:click="startConfirm">{{ __('account_deletion.start') }}</x-button>
                </div>
            @else
                <div class="mt-6 rounded-xl border border-red-300 bg-white/60 p-4">
                    <x-field name="proof" :label="$usesPassword ? __('account_deletion.proof.password_label') : __('account_deletion.proof.email_label')" :hint="$usesPassword ? null : __('account_deletion.proof.email_hint')" required>
                        @if ($usesPassword)
                            <x-input name="proof" type="password" wire:model="proof" autocomplete="current-password" />
                        @else
                            <x-input name="proof" type="email" wire:model="proof" autocomplete="off" />
                        @endif
                    </x-field>

                    <div class="mt-4 flex flex-wrap gap-3">
                        <x-button variant="danger" wire:click="requestDeletion" wire:loading.attr="disabled" wire:target="requestDeletion">
                            {{ __('account_deletion.confirm') }}
                        </x-button>
                        <x-button variant="secondary" wire:click="stopConfirm">{{ __('account_deletion.back') }}</x-button>
                    </div>
                </div>
            @endif
        </x-card>
    @endif
</div>
