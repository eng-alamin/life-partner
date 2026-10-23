<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\StaffPermission;
use App\Models\ContactMessage;
use App\Models\User;

/**
 * Who may read and decide the messages sent through the public contact form.
 * Needs the contact.handle permission and an active account. Members and guests
 * never see a contact message, not even one they wrote themselves.
 */
class ContactMessagePolicy
{
    /** Open the contact inbox. */
    public function viewAny(User $user): bool
    {
        return $user->isActive()
            && $user->checkPermissionTo(StaffPermission::HandleContact->value);
    }

    /** Open one message and change its status. */
    public function handle(User $user, ContactMessage $message): bool
    {
        return $this->viewAny($user);
    }
}
