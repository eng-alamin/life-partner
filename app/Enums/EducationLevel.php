<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum EducationLevel: string
{
    use HasLabel;

    case Ssc = 'ssc';
    case Hsc = 'hsc';
    case Bachelor = 'bachelor';
    case Masters = 'masters';
    case Doctorate = 'doctorate';

    /** Higher means more education. Used for "minimum education" filters. */
    public function rank(): int
    {
        return match ($this) {
            self::Ssc => 1,
            self::Hsc => 2,
            self::Bachelor => 3,
            self::Masters => 4,
            self::Doctorate => 5,
        };
    }
}
