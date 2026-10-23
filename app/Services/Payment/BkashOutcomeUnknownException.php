<?php

declare(strict_types=1);

namespace App\Services\Payment;

/**
 * Thrown when a call that can move money (Execute) got no clear answer: a timeout, a lost connection or a
 * server error. The payment may or may not have happened. The caller must NOT fail the payment: it stays
 * pending and is settled later by asking bKash (Query Payment).
 */
final class BkashOutcomeUnknownException extends BkashException {}
