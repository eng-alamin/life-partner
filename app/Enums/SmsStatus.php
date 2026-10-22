<?php

declare(strict_types=1);

namespace App\Enums;

/** Where one SMS stands. Stored in sms_messages.status. */
enum SmsStatus: string
{
    case Queued = 'queued';
    case Sending = 'sending';
    case Sent = 'sent';
    case Failed = 'failed';
    /** Not sent on purpose, for example the number was removed before the job ran. */
    case Skipped = 'skipped';
}
