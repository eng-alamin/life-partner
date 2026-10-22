<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum GuardianRelation: string
{
    use HasLabel;

    case Father = 'father';
    case Mother = 'mother';
    case Brother = 'brother';
    case Uncle = 'uncle';
    case Other = 'other';
}
