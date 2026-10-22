<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum FamilyFinancialStatus: string
{
    use HasLabel;

    case LowerMiddle = 'lower_middle';
    case Middle = 'middle';
    case UpperMiddle = 'upper_middle';
    case High = 'high';
}
