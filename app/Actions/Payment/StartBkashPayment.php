<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Enums\PaymentGateway;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payment\BkashClient;
use App\Services\Payment\BkashException;
use App\Services\Payment\PaymentEventRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Starts a payment with the bKash API and returns the bKash page the member must be sent to.
 *
 * - The payment (amount, plan snapshot, limits, rules) comes from StartCheckout, so the price is always
 *   ours and the same rules apply as for a manual payment. Opening a session twice for the same plan reuses
 *   the same pending payment and replaces the stored session id.
 * - The call to bKash is made OUTSIDE any database transaction, so no row stays locked while we wait.
 * - Only bKash's session id is stored, together with a short event. If bKash refuses or is unreachable the
 *   payment stays pending and the member sees a plain message; nothing from bKash's answer is shown.
 * - $callbackUrl is built by our own code (the callback route, added in the next step).
 */
final class StartBkashPayment
{
    public function __construct(
        private readonly StartCheckout $checkout,
        private readonly BkashClient $client,
        private readonly PaymentEventRecorder $events,
    ) {}

    public function __invoke(User $user, string $planCode, string $callbackUrl): string
    {
        $payment = ($this->checkout)($user, $planCode, PaymentGateway::Bkash);

        try {
            $session = $this->client->createPayment($payment, $callbackUrl);
        } catch (BkashException $e) {
            $this->events->record($payment, 'gateway_error', ['step' => 'create', 'reason' => $e->getMessage(), 'code' => $e->bkashCode]);

            throw ValidationException::withMessages(['form' => __('bkash.errors.unavailable')]);
        }

        DB::transaction(function () use ($payment, $session): void {
            $locked = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            $locked->forceFill(['gateway_payment_id' => $session['payment_id']])->save();

            $this->events->record($locked, 'gateway_init', ['step' => 'create']);
        });

        return $session['url'];
    }
}
