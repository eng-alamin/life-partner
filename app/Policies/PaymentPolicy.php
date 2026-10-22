<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\StaffPermission;
use App\Models\Payment;
use App\Models\User;

/**
 * Member side: a member sees and acts on their own payments, nobody else's.
 *
 * Staff side: only an active account holding the payments.manage permission (admin)
 * may open the payment queue, see a payment's proof, and approve, reject or refund.
 * Nobody decides a payment of their own account.
 */
class PaymentPolicy
{
    /** Start a checkout: an active member with a verified email. */
    public function create(User $user): bool
    {
        return $user->isActive() && $user->hasVerifiedEmail();
    }

    /** Open or change one's own payment. */
    public function manage(User $user, Payment $payment): bool
    {
        return $user->isActive()
            && $user->hasVerifiedEmail()
            && $payment->user_id !== null
            && (int) $payment->user_id === (int) $user->getKey();
    }

    /** Open the payment queue. */
    public function viewAny(User $user): bool
    {
        return $user->isActive()
            && $user->checkPermissionTo(StaffPermission::ManagePayments->value);
    }

    /** Open one payment as staff and decide it (approve, reject, refund). Never one's own. */
    public function review(User $user, Payment $payment): bool
    {
        return $this->viewAny($user)
            && ($payment->user_id === null || (int) $payment->user_id !== (int) $user->getKey());
    }

    /** Open the screenshot: its owner, or payment staff. */
    public function viewProof(User $user, Payment $payment): bool
    {
        if ($payment->proof_path === null) {
            return false;
        }

        return $this->manage($user, $payment) || $this->review($user, $payment);
    }
}
