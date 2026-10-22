<x-layouts::marketing :title="__('home.title')" :description="__('home.description')">
    <section class="mx-auto w-full max-w-6xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8 lg:py-20">
        <div class="mx-auto max-w-3xl text-center">
            <p class="glass-sm inline-flex items-center rounded-full px-4 py-1.5 text-sm font-medium text-brand-800">{{ __('home.badge') }}</p>
            <h1 class="mt-5 text-4xl font-bold leading-tight text-brand-900 sm:text-5xl lg:text-6xl">{{ __('home.heading') }}</h1>
            <p class="mt-5 text-lg text-stone-600 sm:text-xl">{{ __('home.lead') }}</p>

            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                @if (Route::has('register') && app(\App\Services\Settings\SiteSettings::class)->registrationOpen())
                    <x-button :href="route('register')" size="lg">{{ __('home.cta_create') }}</x-button>
                @endif
                @if (Route::has('biodatas.index'))
                    <x-button :href="route('biodatas.index')" variant="secondary" size="lg">{{ __('home.cta_browse') }}</x-button>
                @endif
            </div>
        </div>
    </section>

    <section class="mx-auto w-full max-w-6xl px-4 pb-12 sm:px-6 lg:px-8" aria-labelledby="steps-heading">
        <h2 id="steps-heading" class="text-center text-2xl font-bold text-brand-800">{{ __('home.steps_title') }}</h2>
        <ol class="mt-6 grid gap-4 md:grid-cols-3">
            @foreach ([1, 2, 3] as $step)
                <li>
                    <x-card class="h-full">
                        <p class="text-sm font-semibold text-gold-700">{{ \App\Support\Digits::localize($step) }}</p>
                        <h3 class="mt-1 text-lg font-semibold text-stone-900">{{ __('home.step'.$step.'_title') }}</h3>
                        <p class="mt-2 text-stone-600">{{ __('home.step'.$step.'_text') }}</p>
                    </x-card>
                </li>
            @endforeach
        </ol>
    </section>

    <section class="mx-auto w-full max-w-6xl px-4 pb-16 sm:px-6 lg:px-8" aria-labelledby="trust-heading">
        <x-card variant="mint">
            <h2 id="trust-heading" class="text-xl font-bold text-brand-800">{{ __('home.trust_title') }}</h2>
            <ul class="mt-3 list-disc space-y-1 ps-6 text-stone-700">
                @foreach ([1, 2, 3] as $item)
                    <li>{{ __('home.trust'.$item) }}</li>
                @endforeach
            </ul>
        </x-card>
    </section>
</x-layouts::marketing>
