<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-brand-800">{{ __('admin_pages.form.heading', ['page' => $label]) }}</h1>
        <x-button :href="route('admin.content')" variant="secondary" size="sm">{{ __('admin_pages.form.back') }}</x-button>
    </div>

    <p class="mt-1 text-stone-600" dir="ltr"><span class="font-mono">{{ $publicUrl }}</span></p>

    @if ($isLegal)
        <x-alert type="warning" class="mt-4">{{ __('admin_pages.form.legal_note') }}</x-alert>
    @endif

    @if ($needsDetails)
        <x-alert type="warning" class="mt-4">{{ __('admin_pages.form.contact_note') }}</x-alert>
    @endif

    @error('form')
        <x-alert type="danger" class="mt-4">{{ $message }}</x-alert>
    @enderror

    <form wire:submit="save" class="mt-4" novalidate>
        <x-card class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="title_bn" :label="__('admin_pages.form.title_bn')" required>
                    <x-input name="title_bn" wire:model="title_bn" maxlength="120" />
                </x-field>
                <x-field name="title_en" :label="__('admin_pages.form.title_en')" required>
                    <x-input name="title_en" wire:model="title_en" maxlength="120" dir="ltr" />
                </x-field>
            </div>

            <x-field name="body_bn" :label="__('admin_pages.form.body_bn')" :hint="__('admin_pages.form.body_hint')">
                <x-textarea name="body_bn" rows="16" wire:model="body_bn" />
            </x-field>

            <x-field name="body_en" :label="__('admin_pages.form.body_en')" :hint="__('admin_pages.form.body_en_hint')">
                <x-textarea name="body_en" rows="16" wire:model="body_en" dir="ltr" />
            </x-field>

            <div>
                <label class="flex items-center gap-3 text-stone-800">
                    <input type="checkbox" wire:model="is_published" class="size-5 accent-brand-600">
                    {{ __('admin_pages.form.published_label') }}
                </label>
                <p class="mt-1 text-sm text-stone-600">{{ __('admin_pages.form.published_hint') }}</p>
                @error('is_published')
                    <p class="text-sm font-medium text-red-700" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <x-button type="submit" wire:loading.attr="disabled">{{ __('admin_pages.form.save') }}</x-button>
        </x-card>
    </form>
</div>
