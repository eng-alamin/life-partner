<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\User;

/**
 * Groups of notifications. A member can switch the email off for a group, never the
 * in-app list. Payment messages are mandatory: they cannot be switched off.
 */
enum NotificationCategory: string
{
    case Interests = 'interests';
    case Profile = 'profile';
    case Payments = 'payments';
    case Staff = 'staff';

    public function isMandatory(): bool
    {
        return $this === self::Payments;
    }

    /** Whether any type in this group can be sent by SMS (see NotificationType::sendsSms). */
    public function sendsSms(): bool
    {
        return $this === self::Interests || $this === self::Payments;
    }

    public function label(): string
    {
        return __('notifications.categories.'.$this->value);
    }

    public function hint(): string
    {
        return __('notifications.category_hints.'.$this->value);
    }

    /**
     * The groups shown on the settings page for this member.
     *
     * @return list<self>
     */
    public static function forUser(User $user): array
    {
        $groups = [self::Interests, self::Profile, self::Payments];

        if ($user->can(StaffPermission::AccessAdmin->value)) {
            $groups[] = self::Staff;
        }

        return $groups;
    }

    /**
     * The groups that show an SMS switch.
     *
     * @return list<self>
     */
    public static function smsGroups(): array
    {
        return array_values(array_filter(self::cases(), static fn (self $c): bool => $c->sendsSms()));
    }
}
