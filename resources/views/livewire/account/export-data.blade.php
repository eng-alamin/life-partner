<div>
    <h1 class="text-2xl font-bold text-brand-800">{{ __('account_export.heading') }}</h1>

    @error('form')
        <x-alert type="danger" class="mt-4">{{ $message }}</x-alert>
    @enderror

    <x-card class="mt-4">
        <p class="text-stone-700">{{ __('account_export.intro') }}</p>

        <h2 class="mt-5 text-lg font-semibold text-stone-900">{{ __('account_export.includes.heading') }}</h2>
        <ul class="mt-2 list-disc space-y-1.5 ps-6 text-stone-700">
            @foreach (__('account_export.includes.items') as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>

        <h2 class="mt-5 text-lg font-semibold text-stone-900">{{ __('account_export.excludes.heading') }}</h2>
        <ul class="mt-2 list-disc space-y-1.5 ps-6 text-stone-700">
            @foreach (__('account_export.excludes.items') as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>

        <x-alert type="warning" class="mt-5">{{ __('account_export.keep_safe') }}</x-alert>

        <form wire:submit="prepare" class="mt-6 rounded-xl border border-stone-300 bg-white/60 p-4">
            <x-field name="proof" :label="$usesPassword ? __('account_export.proof.password_label') : __('account_export.proof.email_label')" :hint="$usesPassword ? null : __('account_export.proof.email_hint')" required>
                @if ($usesPassword)
                    <x-input name="proof" type="password" wire:model="proof" autocomplete="current-password" />
                @else
                    <x-input name="proof" type="email" wire:model="proof" autocomplete="off" />
                @endif
            </x-field>

            <div class="mt-4">
                <x-button type="submit" wire:loading.attr="disabled" wire:target="prepare">
                    {{ __('account_export.button') }}
                </x-button>
            </div>
        </form>

        <p class="mt-4 text-sm text-stone-600">
            <a href="{{ route('my-biodata') }}" class="inline-flex min-h-11 items-center font-semibold text-brand-700 underline underline-offset-2">{{ __('account_export.back') }}</a>
        </p>
    </x-card>
</div>
