<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum MarriageTimeline: string
{
    use HasLabel;

    case Within6Months = 'within_6_months';
    case Within1Year = 'within_1_year';
    case Within2Years = 'within_2_years';
}
