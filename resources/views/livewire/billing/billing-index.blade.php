@php use App\Support\Digits; @endphp

<div>
    <h1 class="text-2xl font-bold text-brand-800">{{ __('billing.index.heading') }}</h1>

    @if (in_array(session('bkash_result'), ['paid', 'failed', 'cancelled', 'pending', 'attention', 'ignored'], true))
        <x-alert :type="match (session('bkash_result')) { 'paid' => 'success', 'attention', 'failed' => 'danger', default => 'warning' }" class="mt-4">
            {{ __('bkash.result.'.session('bkash_result')) }}
        </x-alert>
    @endif

    <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <x-card>
            <p class="text-sm text-stone-600">{{ __('billing.index.credits') }}</p>
            <p class="mt-1 text-3xl font-bold text-brand-800">{{ Digits::localize($credits) }}</p>
            <p class="mt-1 text-sm text-stone-600">{{ __('billing.index.credits_hint') }}</p>
        </x-card>

        <x-card>
            <p class="text-sm text-stone-600">{{ __('billing.index.membership') }}</p>
            @if ($membershipEnds)
                <p class="mt-1 text-lg font-semibold text-stone-900">{{ __('billing.index.membership_until', ['date' => Digits::localize($membershipEnds->format('d M Y'))]) }}</p>
            @else
                <p class="mt-1 text-stone-700">{{ __('billing.index.no_membership') }}</p>
            @endif
        </x-card>
    </div>

    <div class="mt-4">
        <x-button :href="route('plans.index')" variant="gold">{{ __('billing.index.buy_more') }}</x-button>
    </div>

    <h2 class="mt-8 text-lg font-semibold text-stone-900">{{ __('billing.index.payments') }}</h2>

    @if ($payments === [])
        <p class="mt-2 text-stone-600">{{ __('billing.index.no_payments') }}</p>
    @else
        <ul class="mt-3 space-y-3">
            @foreach ($payments as $payment)
                <li wire:key="payment-{{ $payment['code'] }}">
                    <x-card variant="small" class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="font-semibold text-stone-900">{{ $payment['plan'] }}</p>
                            <p class="text-sm text-stone-600">{{ $payment['amount'] }} · {{ Digits::localize($payment['date']->format('d M Y')) }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-badge :variant="$payment['status']->badge()">{{ $payment['status']->label() }}</x-badge>
                            @if ($payment['open'])
                                <x-button :href="route('billing.pay', $payment['code'])" variant="secondary" size="sm">{{ __('billing.index.open') }}</x-button>
                            @endif
                        </div>
                    </x-card>
                </li>
            @endforeach
        </ul>
    @endif
</div>
