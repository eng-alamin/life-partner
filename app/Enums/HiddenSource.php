<?php

declare(strict_types=1);

namespace App\Enums;

/** Who hid a biodata. Only the owner may undo an owner-hide; only staff may undo a staff-hide. */
enum HiddenSource: string
{
    case Owner = 'owner';
    case Staff = 'staff';
}
