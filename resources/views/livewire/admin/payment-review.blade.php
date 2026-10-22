@php
    use App\Support\Digits;
@endphp

<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-brand-800">{{ __('admin_payments.review.title') }}</h1>
        <x-button :href="route('admin.payments')" variant="secondary" size="sm">{{ __('admin_payments.review.back') }}</x-button>
    </div>
    <p class="mt-1 text-stone-600">{{ __('admin_payments.review.intro') }}</p>

    <x-card class="mt-4">
        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_payments.review.plan') }}</dt>
                <dd class="font-semibold text-stone-900">{{ $payment->plan_name }}</dd>
            </div>
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_payments.review.amount') }}</dt>
                <dd class="text-lg font-bold text-stone-900">{{ $amount }}</dd>
            </div>
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_payments.review.gives') }}</dt>
                <dd class="font-medium text-stone-900">
                    @if ($payment->credits > 0)
                        {{ __('admin_payments.review.credits', ['count' => Digits::localize($payment->credits)]) }}
                    @endif
                    @if ($payment->duration_days)
                        {{ __('admin_payments.review.days', ['count' => Digits::localize($payment->duration_days)]) }}
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_payments.review.status') }}</dt>
                <dd><x-badge :variant="$payment->status->badge()">{{ $payment->status->label() }}</x-badge></dd>
            </div>
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_payments.review.member') }}</dt>
                <dd class="font-mono font-semibold text-stone-900" dir="ltr">{{ $memberCode ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_payments.review.submitted') }}</dt>
                <dd class="font-medium text-stone-900">{{ $payment->submitted_at?->format('Y-m-d H:i') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_payments.review.payer_number') }}</dt>
                <dd class="font-mono font-semibold text-stone-900" dir="ltr">{{ $payment->payer_number ? Digits::localize($payment->payer_number) : '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_payments.review.trx') }}</dt>
                <dd class="font-mono font-semibold text-stone-900" dir="ltr">{{ $payment->gateway_ref ?? __('admin_payments.review.trx_none') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-stone-600">{{ __('admin_payments.review.reference') }}</dt>
                <dd class="font-mono text-stone-900" dir="ltr">{{ $payment->tran_id }}</dd>
            </div>
            @if ($payment->review_note)
                <div class="sm:col-span-2">
                    <dt class="text-sm text-stone-600">{{ __('admin_payments.review.note') }}</dt>
                    <dd class="whitespace-pre-line font-medium text-stone-900">{{ $payment->review_note }}</dd>
                </div>
            @endif
            @if ($payment->subscription)
                <div class="sm:col-span-2">
                    <dt class="text-sm text-stone-600">{{ __('admin_payments.review.subscription') }}</dt>
                    <dd class="flex flex-wrap items-center gap-2 font-medium text-stone-900">
                        <x-badge :variant="$payment->subscription->status->badge()">{{ $payment->subscription->status->label() }}</x-badge>
                        {{ Digits::localize($payment->subscription->starts_at?->format('d M Y')) }} → {{ Digits::localize($payment->subscription->ends_at?->format('d M Y')) }}
                    </dd>
                </div>
            @endif
        </dl>

        <div class="mt-4">
            <p class="text-sm text-stone-600">{{ __('admin_payments.review.screenshot') }}</p>
            @if ($hasProof)
                <a href="{{ route('billing.proof', $payment->code) }}" target="_blank" rel="noopener" class="mt-2 inline-block">
                    <img src="{{ route('billing.proof', $payment->code) }}" alt="{{ __('admin_payments.review.screenshot_alt') }}" loading="lazy" class="max-h-96 max-w-full rounded-xl border border-stone-300">
                </a>
            @else
                <p class="mt-1 font-medium text-stone-900">{{ __('admin_payments.review.screenshot_none') }}</p>
            @endif
        </div>
    </x-card>

    <x-card class="mt-4">
        <h2 class="text-lg font-semibold text-stone-900">{{ __('admin_payments.review.history') }}</h2>
        <ul class="mt-2 space-y-1 text-stone-700">
            @foreach ($payment->events as $event)
                <li wire:key="event-{{ $event->id }}">
                    <span class="font-medium">{{ __('admin_payments.events.'.$event->event_type) }}</span>
                    <span class="text-sm text-stone-600">· {{ $event->created_at?->format('Y-m-d H:i') }}</span>
                </li>
            @endforeach
        </ul>
    </x-card>

    @error('form')
        <x-alert type="danger" class="mt-4">{{ $message }}</x-alert>
    @enderror

    @if ($canDecide)
        <x-card class="mt-4">
            <h2 class="text-lg font-semibold text-stone-900">{{ __('admin_payments.review.decision') }}</h2>
            <x-alert type="warning" class="mt-3">{{ __('admin_payments.review.check_statement') }}</x-alert>

            <div class="mt-3 space-y-3">
                @if ($needsTrx)
                    <x-field name="trx_id" :label="__('admin_payments.review.trx_label')" :hint="__('admin_payments.review.trx_hint')" required>
                        <x-input name="trx_id" wire:model="trxId" autocomplete="off" autocapitalize="characters" maxlength="24" dir="ltr" />
                    </x-field>
                @endif

                <x-button type="button" wire:click="approve" wire:confirm="{{ __('admin_payments.review.approve_confirm') }}" wire:loading.attr="disabled">{{ __('admin_payments.review.approve') }}</x-button>
            </div>

            <div class="mt-6 space-y-3 border-t border-stone-300 pt-4">
                <x-field name="reason" :label="__('admin_payments.review.reason_label')" :hint="__('admin_payments.review.reason_hint')">
                    <x-textarea name="reason" rows="3" maxlength="255" wire:model="reason" />
                </x-field>

                <x-button type="button" variant="secondary" wire:click="reject" wire:confirm="{{ __('admin_payments.review.reject_confirm') }}" wire:loading.attr="disabled">{{ __('admin_payments.review.reject') }}</x-button>
            </div>
        </x-card>
    @elseif ($canRefund)
        <x-card class="mt-4">
            <h2 class="text-lg font-semibold text-stone-900">{{ __('admin_payments.review.refund_heading') }}</h2>
            <p class="mt-1 text-stone-600">{{ __('admin_payments.review.refund_intro') }}</p>

            <div class="mt-3 space-y-3">
                <label class="flex items-center gap-3 text-stone-800">
                    <input type="checkbox" wire:model="removeCredits" class="size-5 accent-brand-600">
                    {{ __('admin_payments.review.remove_credits') }}
                </label>
                <label class="flex items-center gap-3 text-stone-800">
                    <input type="checkbox" wire:model="cancelSubscription" class="size-5 accent-brand-600">
                    {{ __('admin_payments.review.cancel_subscription') }}
                </label>

                <x-button type="button" variant="secondary" wire:click="refund" wire:confirm="{{ __('admin_payments.review.refund_confirm') }}" wire:loading.attr="disabled">{{ __('admin_payments.review.refund') }}</x-button>
            </div>
        </x-card>
    @else
        <x-alert type="info" class="mt-4">{{ __('admin_payments.review.closed_note') }}</x-alert>
    @endif
</div>
