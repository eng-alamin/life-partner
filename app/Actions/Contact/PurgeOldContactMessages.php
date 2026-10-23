<?php

declare(strict_types=1);

namespace App\Actions\Contact;

use App\Models\ContactMessage;

/**
 * Nightly clean-up of the contact form inbox: every message older than ContactMessage::RETENTION_DAYS
 * (180 days, counted from the day it was sent) is deleted, whatever its status. The contact page and the
 * staff inbox both promise this.
 *
 * - Only ids are read (never the encrypted text), and rows are deleted in small batches, so a large backlog
 *   cannot block the database. One run deletes at most MAX_BATCHES x BATCH_SIZE messages; the rest goes the
 *   next night.
 * - The activity log gets the number of deleted messages only. Never a name, email, phone or text.
 */
final class PurgeOldContactMessages
{
    private const BATCH_SIZE = 500;

    private const MAX_BATCHES = 20;

    /** @return int number of messages deleted */
    public function __invoke(): int
    {
        $cutoff = now()->subDays(max(1, ContactMessage::RETENTION_DAYS));
        $deleted = 0;

        for ($batch = 0; $batch < self::MAX_BATCHES; $batch++) {
            $ids = ContactMessage::query()
                ->where('created_at', '<', $cutoff)
                ->orderBy('id')
                ->limit(self::BATCH_SIZE)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $deleted += ContactMessage::query()->whereKey($ids->all())->delete();
        }

        if ($deleted > 0) {
            activity('moderation')
                ->withProperties(['deleted' => $deleted])
                ->log('contact_message.purged');
        }

        return $deleted;
    }
}
