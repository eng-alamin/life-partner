<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-brand-800">{{ $isEdit ? __('admin_plans.form.title_edit') : __('admin_plans.form.title_create') }}</h1>
        <x-button :href="route('admin.plans')" variant="secondary" size="sm">{{ __('admin_plans.form.back') }}</x-button>
    </div>

    @if ($isEdit)
        <x-alert type="info" class="mt-4">{{ __('admin_plans.form.snapshot_note') }}</x-alert>
    @endif

    @error('form')
        <x-alert type="danger" class="mt-4">{{ $message }}</x-alert>
    @enderror

    <form wire:submit="save" class="mt-4" novalidate>
        <x-card class="space-y-4">
            @if ($isEdit)
                <div>
                    <p class="text-sm text-stone-600">{{ __('admin_plans.form.code_label') }}</p>
                    <p class="font-mono font-semibold text-stone-900" dir="ltr">{{ $planCode }}</p>
                </div>
                <div>
                    <p class="text-sm text-stone-600">{{ __('admin_plans.form.kind_label') }}</p>
                    <p class="font-semibold text-stone-900">{{ $kindOptions[$kind] ?? $kind }}</p>
                    <p class="text-sm text-stone-600">{{ __('admin_plans.form.fixed_hint') }}</p>
                </div>
            @else
                <x-field name="code" :label="__('admin_plans.form.code_label')" :hint="__('admin_plans.form.code_hint')" required>
                    <x-input name="code" wire:model="code" autocomplete="off" maxlength="40" dir="ltr" />
                </x-field>

                <x-field name="kind" :label="__('admin_plans.form.kind_label')" :hint="__('admin_plans.form.kind_hint')" required>
                    <x-select name="kind" wire:model.live="kind">
                        @foreach ($kindOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-select>
                </x-field>
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="name_bn" :label="__('admin_plans.form.name_bn')" required>
                    <x-input name="name_bn" wire:model="name_bn" maxlength="120" />
                </x-field>
                <x-field name="name_en" :label="__('admin_plans.form.name_en')" required>
                    <x-input name="name_en" wire:model="name_en" maxlength="120" dir="ltr" />
                </x-field>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="description_bn" :label="__('admin_plans.form.description_bn')">
                    <x-textarea name="description_bn" rows="3" maxlength="500" wire:model="description_bn" />
                </x-field>
                <x-field name="description_en" :label="__('admin_plans.form.description_en')">
                    <x-textarea name="description_en" rows="3" maxlength="500" wire:model="description_en" dir="ltr" />
                </x-field>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="price" :label="__('admin_plans.form.price_label')" :hint="__('admin_plans.form.price_hint')" required>
                    <x-input name="price" wire:model="price" inputmode="decimal" autocomplete="off" maxlength="11" dir="ltr" />
                </x-field>
                <x-field name="credits" :label="__('admin_plans.form.credits_label')" :hint="$isMembership ? __('admin_plans.form.credits_hint_membership') : __('admin_plans.form.credits_hint_pack')" :required="! $isMembership">
                    <x-input name="credits" wire:model="credits" inputmode="numeric" autocomplete="off" maxlength="5" dir="ltr" />
                </x-field>
            </div>

            @if ($isMembership)
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field name="duration_days" :label="__('admin_plans.form.days_label')" :hint="__('admin_plans.form.days_hint')" required>
                        <x-input name="duration_days" wire:model="duration_days" inputmode="numeric" autocomplete="off" maxlength="4" dir="ltr" />
                    </x-field>
                    <x-field name="daily_interest_limit" :label="__('admin_plans.form.limit_label')" :hint="__('admin_plans.form.limit_hint')">
                        <x-input name="daily_interest_limit" wire:model="daily_interest_limit" inputmode="numeric" autocomplete="off" maxlength="4" dir="ltr" />
                    </x-field>
                </div>
            @endif

            <x-field name="sort_order" :label="__('admin_plans.form.sort_label')" :hint="__('admin_plans.form.sort_hint')">
                <x-input name="sort_order" wire:model="sort_order" inputmode="numeric" autocomplete="off" maxlength="4" dir="ltr" />
            </x-field>

            <div>
                <label class="flex items-center gap-3 text-stone-800">
                    <input type="checkbox" wire:model="is_active" class="size-5 accent-brand-600">
                    {{ __('admin_plans.form.active_label') }}
                </label>
                <p class="mt-1 text-sm text-stone-600">{{ __('admin_plans.form.active_hint') }}</p>
                @error('is_active')
                    <p class="text-sm font-medium text-red-700" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <x-button type="submit" wire:loading.attr="disabled">{{ __('admin_plans.form.save') }}</x-button>
        </x-card>
    </form>
</div>
