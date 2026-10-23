<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Payment\BkashClient;
use App\Services\Payment\BkashException;

/**
 * Settles bKash payments whose Execute answer was unclear, by asking bKash (Query Payment).
 *
 * Only a payment where Execute was really tried can have moved money, so only those are asked about:
 * the ones with an "execute_unknown", "not_completed", "gateway_error" or "settle_failed" event. A checkout the member
 * simply abandoned is never queried, so there is no pointless traffic to bKash.
 *
 * - A pending payment is asked about after a short wait (the member may still be on the callback page),
 *   and up to a day after it was created.
 * - A payment that the stale clean-up cancelled meanwhile is still asked about for a few hours, because
 *   money may have moved. If bKash says "Completed" it is honoured (same checks as the callback).
 * - A payment with a "settle_failed" event (bKash said Completed but saving failed) is asked again
 *   whatever its age. If saving still fails after a day, it is flagged "needs_attention" once and staff are told.
 * - A payment that was already flagged "needs_attention" is not asked again, so staff are not flooded.
 * - Only a "Completed" answer changes anything, through SettleBkashPayment (amount, invoice, TrxID checks,
 *   one transaction, idempotent). Any other answer leaves the payment as it is and writes nothing.
 * - One failing call never stops the others. At most BATCH payments are asked per run.
 */
final class ReconcileBkashPayments
{
    public const BATCH = 100;

    /** Event types written when Execute gave no clear "paid" answer. */
    private const UNCLEAR = ['execute_unknown', 'not_completed', 'gateway_error', 'settle_failed'];

    public function __construct(
        private readonly BkashClient $client,
        private readonly SettleBkashPayment $settle,
    ) {}

    /** @return array{checked: int, paid: int, attention: int, waiting: int, errors: int} */
    public function __invoke(): array
    {
        $counts = ['checked' => 0, 'paid' => 0, 'attention' => 0, 'waiting' => 0, 'errors' => 0];

        if (! $this->client->isEnabled()) {
            return $counts;
        }

        $candidates = Payment::query()
            ->where('gateway', PaymentGateway::Bkash->value)
            ->whereNotNull('gateway_payment_id')
            ->where(function ($query): void {
                $query->where(function ($pending): void {
                    $pending->where('status', PaymentStatus::Pending->value)
                        ->where('created_at', '<=', now()->subMinutes(2))
                        ->where(function ($age): void {
                            $age->where('created_at', '>=', now()->subDay())
                                ->orWhereHas('events', fn ($events) => $events->where('event_type', 'settle_failed'));
                        });
                })->orWhere(function ($stale): void {
                    $stale->where('status', PaymentStatus::Cancelled->value)
                        ->where('failure_reason', 'stale')
                        ->where('updated_at', '>=', now()->subHours(6));
                });
            })
            ->whereHas('events', fn ($events) => $events->whereIn('event_type', self::UNCLEAR))
            ->whereDoesntHave('events', fn ($events) => $events->where('event_type', 'needs_attention'))
            ->orderBy('id')
            ->limit(self::BATCH)
            ->pluck('gateway_payment_id', 'id');

        foreach ($candidates as $id => $bkashPaymentId) {
            $counts['checked']++;

            try {
                $response = $this->client->queryPayment((string) $bkashPaymentId);
            } catch (BkashException) {
                $counts['errors']++;

                continue;
            }

            if (! BkashClient::isCompleted($response)) {
                $counts['waiting']++;

                continue;
            }

            $outcome = $this->settle->settle((int) $id, $response, 'query');

            if ($outcome === SettleBkashPayment::PAID) {
                $counts['paid']++;
            } elseif ($outcome === SettleBkashPayment::ATTENTION) {
                $counts['attention']++;
            } else {
                $counts['waiting']++;
            }
        }

        // After the retries: a payment that could not be saved for a whole day needs a person.
        $stuck = Payment::query()
            ->where('gateway', PaymentGateway::Bkash->value)
            ->where('status', PaymentStatus::Pending->value)
            ->whereHas('events', fn ($events) => $events->where('event_type', 'settle_failed')->where('created_at', '<=', now()->subDay()))
            ->whereDoesntHave('events', fn ($events) => $events->where('event_type', 'needs_attention'))
            ->orderBy('id')
            ->limit(self::BATCH)
            ->pluck('id');

        foreach ($stuck as $id) {
            $this->settle->flagStuck((int) $id);
            $counts['attention']++;
        }

        return $counts;
    }
}
