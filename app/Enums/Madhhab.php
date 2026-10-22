<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum Madhhab: string
{
    use HasLabel;

    case Hanafi = 'hanafi';
    case Shafii = 'shafii';
    case Maliki = 'maliki';
    case Hanbali = 'hanbali';
    case Undisclosed = 'undisclosed';
}
