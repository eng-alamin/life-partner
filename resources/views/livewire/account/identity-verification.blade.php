<div>
    <h1 class="text-2xl font-bold text-brand-800">{{ __('identity.heading') }}</h1>

    @if (session('status'))
        <x-alert type="success" class="mt-4">{{ session('status') }}</x-alert>
    @endif

    @error('form')
        <x-alert type="danger" class="mt-4">{{ $message }}</x-alert>
    @enderror

    <x-card class="mt-4">
        <p class="text-stone-700">{{ __('identity.intro') }}</p>

        <h2 class="mt-5 text-lg font-semibold text-stone-900">{{ __('identity.privacy.heading') }}</h2>
        <ul class="mt-2 list-disc space-y-1.5 ps-6 text-stone-700">
            <li>{{ __('identity.privacy.no_number') }}</li>
            <li>{{ __('identity.privacy.encrypted') }}</li>
            <li>{{ __('identity.privacy.deleted') }}</li>
            <li>{{ __('identity.privacy.staff_only') }}</li>
            <li>{{ __('identity.privacy.badge_only') }}</li>
        </ul>
    </x-card>

    @if ($verified)
        <x-alert type="success" class="mt-4">{{ __('identity.state.verified') }}</x-alert>
    @elseif ($pending)
        <x-alert type="info" class="mt-4">
            {{ __('identity.state.pending', ['date' => $submittedOn, 'days' => $expireDays]) }}
        </x-alert>
    @elseif (! $hasBiodata)
        <x-alert type="warning" class="mt-4">
            {{ __('identity.state.no_biodata') }}
            <a href="{{ route('my-biodata') }}" class="font-semibold underline">{{ __('identity.state.go_biodata') }}</a>
        </x-alert>
    @elseif ($canSubmit)
        @if ($rejected && $latest?->rejection_reason)
            <x-alert type="warning" class="mt-4">
                {{ __('identity.state.rejected', ['reason' => $latest->rejection_reason->label()]) }}
            </x-alert>
        @endif

        <x-card class="mt-4">
            <form wire:submit="submit" class="space-y-5" novalidate>
                <x-field name="documentType" :label="__('identity.form.type')" required>
                    <x-select name="documentType" wire:model.live="documentType">
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field name="front" :label="__('identity.form.front')" :hint="__('identity.form.file_hint', ['max' => $maxMb])" required>
                    <input
                        type="file"
                        id="f-front"
                        name="front"
                        wire:model="front"
                        accept="image/jpeg,image/png,image/webp"
                        aria-describedby="f-front-hint"
                        class="block min-h-11 w-full rounded-xl border border-stone-500 bg-white/70 px-3 py-2 text-stone-900"
                    >
                </x-field>

                @if ($documentType !== 'passport')
                    <x-field name="back" :label="__('identity.form.back')" :hint="__('identity.form.back_hint')">
                        <input
                            type="file"
                            id="f-back"
                            name="back"
                            wire:model="back"
                            accept="image/jpeg,image/png,image/webp"
                            aria-describedby="f-back-hint"
                            class="block min-h-11 w-full rounded-xl border border-stone-500 bg-white/70 px-3 py-2 text-stone-900"
                        >
                    </x-field>
                @endif

                <p class="text-sm text-stone-600">{{ __('identity.form.advice') }}</p>

                <x-button type="submit" wire:loading.attr="disabled" wire:target="submit,front,back">{{ __('identity.form.submit') }}</x-button>
            </form>
        </x-card>
    @else
        <x-alert type="warning" class="mt-4">{{ __('identity.state.closed') }}</x-alert>
    @endif

    <p class="mt-6"><a href="{{ route('my-biodata') }}" class="font-semibold text-brand-700 underline">{{ __('identity.back') }}</a></p>
</div>
