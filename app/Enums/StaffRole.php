<?php

declare(strict_types=1);

namespace App\Enums;

/** Role names stored by spatie/laravel-permission. Members have no role. */
enum StaffRole: string
{
    case Admin = 'admin';
    case Moderator = 'moderator';
}
