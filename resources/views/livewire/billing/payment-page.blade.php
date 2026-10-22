@php
    use App\Enums\PaymentStatus;
    use App\Support\Digits;
@endphp

<div class="mx-auto max-w-2xl">
    <h1 class="text-2xl font-bold text-brand-800">{{ __('billing.pay.heading') }}</h1>

    <x-card class="mt-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <p class="text-sm text-stone-600">{{ __('billing.pay.plan') }}</p>
                <p class="text-lg font-semibold text-stone-900">{{ $payment->plan_name }}</p>
            </div>
            <x-badge :variant="$payment->status->badge()">{{ $payment->status->label() }}</x-badge>
        </div>
        <p class="mt-2 text-2xl font-bold text-brand-800">{{ $amount }}</p>
        <p class="mt-1 text-sm text-stone-600">{{ __('billing.pay.reference') }} <span class="font-mono">{{ $payment->tran_id }}</span></p>
    </x-card>

    @error('form')
        <x-alert type="danger" class="mt-4">{{ $message }}</x-alert>
    @enderror

    @if ($payment->status === PaymentStatus::Pending)
        <x-card class="mt-4">
            <h2 class="text-lg font-semibold text-stone-900">{{ __('billing.pay.steps_heading') }}</h2>
            <ol class="mt-3 list-decimal space-y-2 ps-5 text-stone-800">
                <li>{{ __('billing.pay.step_send', ['amount' => $amount]) }}
                    <p class="mt-1 rounded-xl bg-white/70 p-3 text-center text-xl font-bold tracking-wide text-stone-900" dir="ltr">{{ Digits::localize($bkashNumber) }}</p>
                    <p class="mt-1 text-sm text-stone-600">{{ __('billing.pay.account_type') }}</p>
                </li>
                <li>{{ __('billing.pay.step_note') }}</li>
                <li>{{ __('billing.pay.step_submit') }}</li>
            </ol>
        </x-card>

        <x-card class="mt-4">
            <form wire:submit="submit" class="space-y-4" novalidate>
                <h2 class="text-lg font-semibold text-stone-900">{{ __('billing.pay.form_heading') }}</h2>
                <p class="text-stone-700">{{ __('billing.pay.form_intro') }}</p>

                <x-field name="trx_id" :label="__('billing.pay.trx_label')" :hint="__('billing.pay.trx_hint')">
                    <x-input name="trx_id" wire:model="trxId" autocomplete="off" autocapitalize="characters" maxlength="24" dir="ltr" />
                </x-field>

                <x-field name="payer_number" :label="__('billing.pay.number_label')" :hint="__('billing.pay.number_hint')" required>
                    <x-input name="payer_number" type="tel" inputmode="numeric" wire:model="payerNumber" autocomplete="tel" maxlength="20" dir="ltr" />
                </x-field>

                <x-field name="proof" :label="__('billing.pay.proof_label')" :hint="__('billing.pay.proof_hint', ['max' => Digits::localize($proofMaxMb)])">
                    <input
                        type="file"
                        id="f-proof"
                        name="proof"
                        wire:model="proof"
                        accept="image/jpeg,image/png,image/webp"
                        aria-describedby="f-proof-hint"
                        class="block min-h-11 w-full rounded-xl border border-stone-500 bg-white/70 px-3 py-2 text-stone-900"
                    >
                </x-field>

                <div class="flex flex-wrap gap-2">
                    <x-button type="submit" wire:loading.attr="disabled" wire:target="submit,proof">{{ __('billing.pay.submit') }}</x-button>
                    <x-button variant="secondary" wire:click="cancel" wire:confirm="{{ __('billing.pay.confirm_cancel') }}" wire:loading.attr="disabled" wire:target="cancel">{{ __('billing.pay.cancel') }}</x-button>
                </div>
            </form>
        </x-card>
    @elseif ($payment->status === PaymentStatus::UnderReview)
        <x-alert type="info" class="mt-4">{{ __('billing.pay.under_review') }}</x-alert>

        <x-card class="mt-4">
            <dl class="space-y-2 text-stone-800">
                @if (filled($payment->gateway_ref))
                    <div><dt class="text-sm text-stone-600">{{ __('billing.pay.trx_label') }}</dt><dd class="font-mono" dir="ltr">{{ $payment->gateway_ref }}</dd></div>
                @endif
                <div><dt class="text-sm text-stone-600">{{ __('billing.pay.number_label') }}</dt><dd dir="ltr">{{ Digits::localize($payment->payer_number) }}</dd></div>
                @if ($payment->proof_path)
                    <div><dt class="text-sm text-stone-600">{{ __('billing.pay.proof_label') }}</dt><dd><a href="{{ route('billing.proof', $payment->code) }}" target="_blank" rel="noopener" class="font-semibold text-brand-700 underline">{{ __('billing.pay.proof_view') }}</a></dd></div>
                @endif
            </dl>
        </x-card>
    @elseif ($payment->status === PaymentStatus::Paid)
        <x-alert type="success" class="mt-4">{{ __('billing.pay.paid') }}</x-alert>
    @else
        <x-alert type="warning" class="mt-4">{{ __('billing.pay.closed') }}</x-alert>
    @endif

    <p class="mt-6"><a href="{{ route('billing.index') }}" class="font-semibold text-brand-700 underline">{{ __('billing.pay.back') }}</a></p>
</div>
