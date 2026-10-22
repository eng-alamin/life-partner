<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum PlanKind: string
{
    use HasLabel;

    /** A pack of contact unlock credits. */
    case CreditPack = 'credit_pack';

    /** A period of membership (higher daily interest limit, optional credits). */
    case Membership = 'membership';
}
