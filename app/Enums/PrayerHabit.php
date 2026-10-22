<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum PrayerHabit: string
{
    use HasLabel;

    case FiveOnTime = 'five_on_time';
    case MostlyRegular = 'mostly_regular';
    case Sometimes = 'sometimes';
    case Learning = 'learning';
}
