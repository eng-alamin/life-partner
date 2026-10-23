<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="break-words text-2xl font-bold text-brand-800">{{ $user->name }}</h1>
        <x-button :href="route('admin.users')" variant="secondary" size="sm">{{ __('admin_users.show.back') }}</x-button>
    </div>
    <p class="mt-1 text-stone-600">{{ __('admin_users.show.intro') }}</p>

    <x-card class="mt-4">
        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_users.show.email') }}</dt>
                <dd class="break-all font-medium text-stone-900" dir="ltr">{{ $user->email }}</dd>
            </div>
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_users.show.status') }}</dt>
                <dd class="flex flex-wrap items-center gap-2">
                    <x-badge :variant="$user->status->badge()">{{ $user->status->label() }}</x-badge>
                    @if ($roleLabel)
                        <x-badge variant="gold">{{ $roleLabel }}</x-badge>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_users.show.email_verified') }}</dt>
                <dd class="font-medium text-stone-900">{{ $user->email_verified_at ? __('admin_users.show.yes') : __('admin_users.show.no') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_users.show.phone_verified') }}</dt>
                <dd class="font-medium text-stone-900">{{ $user->phone_verified_at ? __('admin_users.show.yes') : __('admin_users.show.no') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_users.show.joined') }}</dt>
                <dd class="font-medium text-stone-900">{{ $user->created_at?->format('Y-m-d H:i') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_users.show.last_login') }}</dt>
                <dd class="font-medium text-stone-900">{{ $user->last_login_at?->format('Y-m-d H:i') ?? __('admin_users.show.never') }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-sm text-stone-600">{{ __('admin_users.show.biodata') }}</dt>
                <dd class="flex flex-wrap items-center gap-3">
                    @if ($user->biodata)
                        <span class="font-mono font-semibold text-stone-900" dir="ltr">{{ $user->biodata->public_code }}</span>
                        <x-badge :variant="$user->biodata->status->badge()">{{ $user->biodata->status->label() }}</x-badge>
                        @if ($canReviewBiodata)
                            <x-button :href="route('admin.approvals.show', $user->biodata->public_code)" variant="secondary" size="sm">{{ __('admin_users.show.view_biodata') }}</x-button>
                        @endif
                    @else
                        <span class="font-medium text-stone-900">{{ __('admin_users.show.no_biodata') }}</span>
                    @endif
                </dd>
            </div>
            @if ($user->suspended_at)
                <div>
                    <dt class="text-sm text-stone-600">{{ __('admin_users.show.suspended_at') }}</dt>
                    <dd class="font-medium text-stone-900">{{ $user->suspended_at->format('Y-m-d H:i') }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm text-stone-600">{{ __('admin_users.show.suspension_reason') }}</dt>
                    <dd class="whitespace-pre-line font-medium text-stone-900">{{ $user->suspension_reason ?? '—' }}</dd>
                </div>
            @endif
        </dl>
    </x-card>

    @error('form')
        <x-alert type="danger" class="mt-4">{{ $message }}</x-alert>
    @enderror

    @if ($isSelf)
        <x-alert type="info" class="mt-4">{{ __('admin_users.show.self_note') }}</x-alert>
    @endif

    @if ($canSuspend)
        <x-card class="mt-4">
            <h2 class="text-lg font-semibold text-stone-900">{{ __('admin_users.show.suspend_heading') }}</h2>
            <p class="mt-1 text-stone-600">{{ __('admin_users.show.suspend_text') }}</p>
            <p class="mt-2 text-stone-600">{{ __('admin_users.show.suspend_cancels') }}</p>

            <div class="mt-3 space-y-3">
                <x-field name="reason" :label="__('admin_users.show.reason_label')" :hint="__('admin_users.show.reason_hint')" required>
                    <x-textarea name="reason" rows="3" maxlength="500" wire:model="reason" />
                </x-field>

                <x-button type="button" variant="danger" wire:click="suspend" wire:confirm="{{ __('admin_users.show.suspend_confirm') }}" wire:loading.attr="disabled">{{ __('admin_users.show.suspend_button') }}</x-button>
            </div>
        </x-card>
    @endif

    @if ($canReactivate)
        <x-card class="mt-4">
            <h2 class="text-lg font-semibold text-stone-900">{{ __('admin_users.show.reactivate_heading') }}</h2>
            <p class="mt-1 text-stone-600">{{ __('admin_users.show.reactivate_text') }}</p>
            <x-button type="button" class="mt-3" wire:click="reactivate" wire:confirm="{{ __('admin_users.show.reactivate_confirm') }}" wire:loading.attr="disabled">{{ __('admin_users.show.reactivate_button') }}</x-button>
        </x-card>
    @endif

    @if ($canMakeModerator || $canRemoveModerator || $isAdminRole)
        <x-card class="mt-4">
            <h2 class="text-lg font-semibold text-stone-900">{{ __('admin_users.show.staff_heading') }}</h2>

            @if ($isAdminRole)
                <p class="mt-1 text-stone-600">{{ __('admin_users.show.admin_note') }}</p>
            @endif

            @if ($canMakeModerator)
                <p class="mt-1 text-stone-600">{{ __('admin_users.show.moderator_grant_text') }}</p>
                <x-button type="button" class="mt-3" wire:click="makeModerator" wire:confirm="{{ __('admin_users.show.moderator_grant_confirm') }}" wire:loading.attr="disabled">{{ __('admin_users.show.moderator_grant_button') }}</x-button>
            @endif

            @if ($canRemoveModerator)
                <p class="mt-1 text-stone-600">{{ __('admin_users.show.moderator_remove_text') }}</p>
                <x-button type="button" variant="secondary" class="mt-3" wire:click="removeModerator" wire:confirm="{{ __('admin_users.show.moderator_remove_confirm') }}" wire:loading.attr="disabled">{{ __('admin_users.show.moderator_remove_button') }}</x-button>
            @endif
        </x-card>
    @endif
</div>
