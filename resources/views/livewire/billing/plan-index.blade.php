<div>
    <h1 class="text-2xl font-bold text-brand-800">{{ __('billing.plans.heading') }}</h1>
    <p class="mt-1 text-stone-700">{{ __('billing.plans.intro') }}</p>

    @error('form')
        <x-alert type="danger" class="mt-4">{{ $message }}</x-alert>
    @enderror

    @if (! $configured)
        <x-alert type="warning" class="mt-4">{{ __('billing.plans.not_configured') }}</x-alert>
    @endif

    @if ($plans === [])
        <x-empty-state class="mt-6" :title="__('billing.plans.none_title')" :text="__('billing.plans.none_text')" />
    @else
        <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($plans as $plan)
                <li wire:key="plan-{{ $plan['code'] }}">
                    <x-card class="flex h-full flex-col">
                        <x-badge variant="gold" class="self-start">{{ $plan['kind']->label() }}</x-badge>
                        <h2 class="mt-2 text-lg font-semibold text-stone-900">{{ $plan['name'] }}</h2>
                        <p class="mt-1 text-2xl font-bold text-brand-800">{{ $plan['price'] }}</p>

                        <ul class="mt-3 space-y-1 text-stone-700">
                            @if ($plan['credits'] > 0)
                                <li>{{ __('billing.plans.credits', ['count' => \App\Support\Digits::localize($plan['credits'])]) }}</li>
                            @endif
                            @if ($plan['days'])
                                <li>{{ __('billing.plans.days', ['count' => \App\Support\Digits::localize($plan['days'])]) }}</li>
                            @endif
                            @if ($plan['dailyLimit'])
                                <li>{{ __('billing.plans.daily_limit', ['count' => \App\Support\Digits::localize($plan['dailyLimit'])]) }}</li>
                            @endif
                        </ul>

                        @if (filled($plan['description']))
                            <p class="mt-3 text-stone-600">{{ $plan['description'] }}</p>
                        @endif

                        <div class="mt-auto pt-4">
                            @auth
                                <x-button wire:click="buy('{{ $plan['code'] }}')" wire:loading.attr="disabled" wire:target="buy" class="w-full">{{ __('billing.plans.buy') }}</x-button>
                            @else
                                <x-button :href="route('login')" class="w-full">{{ __('billing.plans.login_to_buy') }}</x-button>
                            @endauth
                        </div>
                    </x-card>
                </li>
            @endforeach
        </ul>
    @endif

    <p class="mt-6 text-sm text-stone-600">{{ __('billing.plans.how') }}</p>
</div>
