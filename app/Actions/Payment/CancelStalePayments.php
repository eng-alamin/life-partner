<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Payment\PaymentEventRecorder;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Cancels checkouts that the member never finished: still "pending" after
 * lifepartner.billing.stale_minutes. A payment the member already submitted
 * (under_review) is never touched here: only staff decide it.
 *
 * Each payment is locked and checked again before it changes, so a member who
 * submits at the same moment is not cancelled by mistake. Returns how many were cancelled.
 */
final class CancelStalePayments
{
    public function __construct(private readonly PaymentEventRecorder $events) {}

    public function __invoke(): int
    {
        $minutes = max(1, (int) config('lifepartner.billing.stale_minutes'));
        $cutoff = now()->subMinutes($minutes);
        $cancelled = 0;

        Payment::query()
            ->where('status', PaymentStatus::Pending->value)
            ->where('created_at', '<', $cutoff)
            ->orderBy('id')
            ->pluck('id')
            ->chunk(100)
            ->each(function ($ids) use ($cutoff, &$cancelled): void {
                foreach ($ids as $id) {
                    $cancelled += $this->cancel((int) $id, $cutoff) ? 1 : 0;
                }
            });

        return $cancelled;
    }

    private function cancel(int $id, CarbonInterface $cutoff): bool
    {
        return DB::transaction(function () use ($id, $cutoff): bool {
            $payment = Payment::query()->whereKey($id)->lockForUpdate()->first();

            if ($payment === null || $payment->status !== PaymentStatus::Pending || $payment->created_at->greaterThanOrEqualTo($cutoff)) {
                return false;
            }

            $payment->forceFill([
                'status' => PaymentStatus::Cancelled->value,
                'failure_reason' => 'stale',
            ])->save();

            $this->events->record($payment, 'cancelled', ['by' => 'system']);

            return true;
        });
    }
}
