<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum MaritalStatus: string
{
    use HasLabel;

    case NeverMarried = 'never_married';
    case Divorced = 'divorced';
    case Widowed = 'widowed';
}
