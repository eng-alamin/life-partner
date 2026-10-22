<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum PracticingLevel: string
{
    use HasLabel;

    case Beginner = 'beginner';
    case Trying = 'trying';
    case Practicing = 'practicing';

    /** Higher means more practicing. Used for "minimum level" filters. */
    public function rank(): int
    {
        return match ($this) {
            self::Beginner => 1,
            self::Trying => 2,
            self::Practicing => 3,
        };
    }
}
