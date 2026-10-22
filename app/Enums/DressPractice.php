<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum DressPractice: string
{
    use HasLabel;

    case Always = 'always';
    case Sometimes = 'sometimes';
    case No = 'no';
}
