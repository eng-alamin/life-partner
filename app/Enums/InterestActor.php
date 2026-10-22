<?php

declare(strict_types=1);

namespace App\Enums;

/** Who caused a change of an interest, stored in the status history. */
enum InterestActor: string
{
    case Sender = 'sender';
    case Receiver = 'receiver';
    case Guardian = 'guardian';
    case System = 'system';
}
