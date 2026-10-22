<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum SpouseWorkAfterMarriage: string
{
    use HasLabel;

    case Yes = 'yes';
    case No = 'no';
    case Open = 'open';
}
