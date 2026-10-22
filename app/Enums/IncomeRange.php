<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum IncomeRange: string
{
    use HasLabel;

    case Below30k = 'below_30k';
    case From30kTo60k = 'from_30k_to_60k';
    case From60kTo100k = 'from_60k_to_100k';
    case Above100k = 'above_100k';
    case Undisclosed = 'undisclosed';
}
