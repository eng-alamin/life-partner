<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum QuranReading: string
{
    use HasLabel;

    case Tajweed = 'tajweed';
    case CanRead = 'can_read';
    case Learning = 'learning';
}
