<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum Complexion: string
{
    use HasLabel;

    case Fair = 'fair';
    case Medium = 'medium';
    case Brown = 'brown';
    case Dark = 'dark';
}
