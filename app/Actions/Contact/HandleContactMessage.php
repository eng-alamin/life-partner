<?php

declare(strict_types=1);

namespace App\Actions\Contact;

use App\Enums\ContactMessageStatus;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Staff change the status of one contact message: handled, spam, or open again.
 *
 * - The message is found by its id (kept locked in the page) and locked while it changes.
 * - Permission is checked again here, on the server, for every change.
 * - The reply itself is written by the staff member in their own email program; nothing is sent from here.
 * - The activity log gets the action name and the message row as subject. Never the sender or the text.
 */
final class HandleContactMessage
{
    public function markHandled(User $staff, int $messageId): ContactMessage
    {
        return $this->change($staff, $messageId, ContactMessageStatus::Handled, 'handled');
    }

    public function markSpam(User $staff, int $messageId): ContactMessage
    {
        return $this->change($staff, $messageId, ContactMessageStatus::Spam, 'spam');
    }

    public function reopen(User $staff, int $messageId): ContactMessage
    {
        return $this->change($staff, $messageId, ContactMessageStatus::New, 'reopened');
    }

    private function change(User $staff, int $messageId, ContactMessageStatus $to, string $action): ContactMessage
    {
        return DB::transaction(function () use ($staff, $messageId, $to, $action): ContactMessage {
            $message = ContactMessage::query()->whereKey($messageId)->lockForUpdate()->firstOrFail();

            Gate::forUser($staff)->authorize('handle', $message);

            if ($message->status === $to) {
                throw ValidationException::withMessages(['form' => __('admin_contact.errors.already')]);
            }

            $open = $to === ContactMessageStatus::New;

            $message->forceFill([
                'status' => $to->value,
                'handled_by' => $open ? null : $staff->getKey(),
                'handled_at' => $open ? null : now(),
            ])->save();

            activity('moderation')
                ->causedBy($staff)
                ->performedOn($message)
                ->withProperties(['action' => $action])
                ->log('contact_message.'.$action);

            return $message;
        });
    }
}
