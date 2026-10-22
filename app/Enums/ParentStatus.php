<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum ParentStatus: string
{
    use HasLabel;

    case Living = 'living';
    case Deceased = 'deceased';
}
