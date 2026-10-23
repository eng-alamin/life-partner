<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-brand-800">{{ $story === null ? __('admin_stories.form.title_new') : __('admin_stories.form.title_edit') }}</h1>
        <x-button :href="route('admin.stories')" variant="secondary" size="sm">{{ __('admin_stories.form.back') }}</x-button>
    </div>

    @if ($story !== null)
        <p class="mt-2 flex flex-wrap items-center gap-2 text-stone-700">
            <x-badge :variant="$story->status->badge()">{{ $story->status->label() }}</x-badge>
            <x-badge :variant="$consentState === 'given' ? 'brand' : ($consentState === 'withdrawn' ? 'red' : 'gold')">{{ __('admin_stories.consent_state.'.$consentState) }}</x-badge>
            @if ($story->status->value === 'published')
                <a href="{{ $publicUrl }}" class="font-mono text-sm text-brand-700 underline" dir="ltr">{{ $publicUrl }}</a>
            @endif
        </p>
    @endif

    <x-alert type="warning" class="mt-4">{{ __('admin_stories.form.privacy_note') }}</x-alert>

    @error('form')
        <x-alert type="danger" class="mt-4">{{ $message }}</x-alert>
    @enderror

    <form wire:submit="save" class="mt-4 space-y-4" novalidate>
        <x-card class="space-y-4">
            <h2 class="text-lg font-semibold text-stone-900">{{ __('admin_stories.form.story_heading') }}</h2>

            <x-field name="couple_label" :label="__('admin_stories.form.couple_label')" :hint="__('admin_stories.form.couple_label_hint')" required>
                <x-input name="couple_label" wire:model="couple_label" maxlength="60" />
            </x-field>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="title_bn" :label="__('admin_stories.form.title_bn')">
                    <x-input name="title_bn" wire:model="title_bn" maxlength="120" />
                </x-field>
                <x-field name="title_en" :label="__('admin_stories.form.title_en')">
                    <x-input name="title_en" wire:model="title_en" maxlength="120" dir="ltr" />
                </x-field>
            </div>

            <x-field name="body_bn" :label="__('admin_stories.form.body_bn')" :hint="__('admin_stories.form.body_hint')">
                <x-textarea name="body_bn" rows="10" wire:model="body_bn" />
            </x-field>

            <x-field name="body_en" :label="__('admin_stories.form.body_en')" :hint="__('admin_stories.form.body_hint')">
                <x-textarea name="body_en" rows="10" wire:model="body_en" dir="ltr" />
            </x-field>
        </x-card>

        <x-card class="space-y-4">
            <h2 class="text-lg font-semibold text-stone-900">{{ __('admin_stories.form.consent_heading') }}</h2>
            <p class="text-sm text-stone-600">{{ __('admin_stories.form.consent_intro') }}</p>

            <div class="grid gap-4 sm:grid-cols-3">
                <x-field name="consent_given_by" :label="__('admin_stories.form.consent_by')">
                    <x-select name="consent_given_by" wire:model="consent_given_by">
                        <option value="">{{ __('admin_stories.form.choose') }}</option>
                        @foreach ($givers as $giver)
                            <option value="{{ $giver->value }}">{{ $giver->label() }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field name="consent_given_at" :label="__('admin_stories.form.consent_at')">
                    <x-input name="consent_given_at" type="date" wire:model="consent_given_at" dir="ltr" />
                </x-field>

                <x-field name="consent_form" :label="__('admin_stories.form.consent_form')">
                    <x-select name="consent_form" wire:model="consent_form">
                        <option value="">{{ __('admin_stories.form.choose') }}</option>
                        @foreach ($forms as $form)
                            <option value="{{ $form->value }}">{{ $form->label() }}</option>
                        @endforeach
                    </x-select>
                </x-field>
            </div>

            <x-field name="consent_note" :label="__('admin_stories.form.consent_note')" :hint="__('admin_stories.form.consent_note_hint')">
                <x-input name="consent_note" wire:model="consent_note" maxlength="200" />
            </x-field>
        </x-card>

        <div class="flex flex-wrap items-center gap-3">
            <x-button type="submit" wire:loading.attr="disabled">{{ __('admin_stories.form.save') }}</x-button>

            @if ($story !== null)
                @if ($story->status->value === 'draft')
                    <x-button type="button" variant="gold" wire:click="publish" wire:confirm="{{ __('admin_stories.form.confirm_publish') }}" wire:loading.attr="disabled">{{ __('admin_stories.form.publish') }}</x-button>
                @else
                    <x-button type="button" variant="secondary" wire:click="unpublish" wire:loading.attr="disabled">{{ __('admin_stories.form.unpublish') }}</x-button>
                @endif

                @if ($consentState === 'given')
                    <x-button type="button" variant="secondary" wire:click="withdraw" wire:confirm="{{ __('admin_stories.form.confirm_withdraw') }}" wire:loading.attr="disabled">{{ __('admin_stories.form.withdraw') }}</x-button>
                @endif

                <x-button type="button" variant="danger" wire:click="delete" wire:confirm="{{ __('admin_stories.form.confirm_delete') }}" wire:loading.attr="disabled">{{ __('admin_stories.form.delete') }}</x-button>
            @endif
        </div>
    </form>
</div>
