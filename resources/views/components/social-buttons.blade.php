@php
    $providers = collect(\App\Enums\SocialProvider::cases())->filter(fn ($provider) => $provider->isConfigured());
@endphp

@if ($providers->isNotEmpty())
    <div {{ $attributes->class(['mt-6']) }}>
        <div class="flex items-center gap-3 text-sm text-stone-600">
            <span class="h-px flex-1 bg-stone-300" aria-hidden="true"></span>
            {{ __('account.social.or') }}
            <span class="h-px flex-1 bg-stone-300" aria-hidden="true"></span>
        </div>

        <div class="mt-4 grid gap-3">
            @foreach ($providers as $provider)
                {{-- Plain link on purpose: this leaves the site, so no wire:navigate. --}}
                <x-button :href="route('social.redirect', ['provider' => $provider->value])" variant="secondary" class="w-full">
                    {{ __('account.social.continue_with', ['provider' => $provider->label()]) }}
                </x-button>
            @endforeach
        </div>
    </div>
@endif
