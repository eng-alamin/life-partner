<x-card class="mt-4">
    <h2 class="text-lg font-semibold text-stone-900">{{ __('photos.heading') }}</h2>
    <p class="mt-1 text-stone-700">{{ __('photos.intro', ['max' => $max]) }}</p>

    @if ($notice !== '')
        <x-alert type="success" class="mt-3">{{ $notice }}</x-alert>
    @endif

    @if ($photos === [])
        <p class="mt-4 text-stone-600">{{ __('photos.none') }}</p>
    @else
        <ul class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3">
            @foreach ($photos as $photo)
                <li wire:key="photo-{{ $photo['code'] }}" class="space-y-2">
                    @if ($photo['thumb'])
                        <img src="{{ $photo['thumb'] }}" alt="{{ __('photos.alt') }}" width="360" height="450" loading="lazy" class="aspect-[4/5] w-full rounded-xl object-cover">
                    @else
                        <div class="flex aspect-[4/5] w-full items-center justify-center rounded-xl border border-stone-300 bg-stone-100 p-2 text-center text-sm text-stone-600">
                            {{ __('photos.removed') }}
                        </div>
                    @endif

                    <div class="flex flex-wrap items-center gap-1.5">
                        <x-badge :variant="$photo['status']->badge()">{{ $photo['status']->label() }}</x-badge>
                        @if ($photo['is_main'])
                            <x-badge variant="gold">{{ __('photos.main') }}</x-badge>
                        @endif
                    </div>

                    @if (filled($photo['reason']))
                        <p class="text-sm text-red-800">{{ __('photos.reason') }} {{ $photo['reason'] }}</p>
                    @endif

                    <div class="flex flex-wrap gap-2">
                        @if (! $photo['is_main'] && $photo['status'] !== \App\Enums\PhotoStatus::Rejected)
                            <x-button variant="secondary" size="sm" wire:click="makeMain({{ $photo['id'] }})" wire:loading.attr="disabled">{{ __('photos.make_main') }}</x-button>
                        @endif
                        <x-button variant="secondary" size="sm" wire:click="delete({{ $photo['id'] }})" wire:confirm="{{ __('photos.confirm_delete') }}" wire:loading.attr="disabled">{{ __('photos.delete') }}</x-button>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($canUpload)
        <div class="mt-5 space-y-3">
            <x-field name="upload" :label="__('photos.choose')" :hint="__('photos.rules')">
                <input
                    type="file"
                    id="f-upload"
                    name="upload"
                    wire:model="upload"
                    accept="image/jpeg,image/png,image/webp"
                    aria-describedby="f-upload-hint"
                    class="block min-h-11 w-full rounded-xl border border-stone-500 bg-white/70 px-3 py-2 text-stone-900"
                >
            </x-field>

            <x-button wire:click="save" wire:loading.attr="disabled" wire:target="save,upload">{{ __('photos.upload') }}</x-button>
        </div>
    @else
        <p class="mt-4 text-stone-600">{{ __('photos.full', ['max' => $max]) }}</p>
    @endif

    <form wire:submit="saveVisibility" class="mt-6 space-y-3 border-t border-stone-200 pt-5" novalidate>
        <x-field name="visibility" :label="__('photos.visibility_label')" :hint="__('photos.visibility_hint')">
            <x-select name="visibility" wire:model="visibility">
                @foreach ($visibilityOptions as $option)
                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                @endforeach
            </x-select>
        </x-field>

        <x-button type="submit" variant="secondary" wire:loading.attr="disabled" wire:target="saveVisibility">{{ __('photos.visibility_save') }}</x-button>
    </form>
</x-card>
