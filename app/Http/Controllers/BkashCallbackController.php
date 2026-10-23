<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Payment\SettleBkashPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Where bKash sends the member back. Everything in the address is untrusted: it is only used to
 * find the member's own payment, and SettleBkashPayment decides with bKash's own answer.
 * The member always lands on "My payments" with a short result note.
 */
final class BkashCallbackController extends Controller
{
    public function __invoke(Request $request, SettleBkashPayment $settle): RedirectResponse
    {
        $paymentId = $request->query('paymentID');
        $status = $request->query('status');

        $valid = is_string($paymentId)
            && preg_match('/^[A-Za-z0-9_-]{1,64}$/', $paymentId) === 1
            && in_array($status, ['success', 'failure', 'cancel'], true);

        $outcome = $valid
            ? $settle->fromCallback($request->user(), $paymentId, (string) $status)
            : SettleBkashPayment::IGNORED;

        return redirect()->route('billing.index')->with('bkash_result', $outcome);
    }
}
