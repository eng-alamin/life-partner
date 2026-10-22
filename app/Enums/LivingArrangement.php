<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum LivingArrangement: string
{
    use HasLabel;

    case SeparateHome = 'separate_home';
    case WithFamily = 'with_family';
    case Open = 'open';
}
