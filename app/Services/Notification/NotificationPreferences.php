<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Enums\NotificationCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Which groups of notifications a member wants by email. The default is "yes".
 * Mandatory groups (payments) ignore the setting. The in-app list is never switched off.
 * SMS has its own switch per group, only for the groups that can send SMS.
 */
final class NotificationPreferences
{
    public function emailEnabled(User $user, NotificationCategory $category): bool
    {
        if ($category->isMandatory()) {
            return true;
        }

        $value = DB::table('notification_preferences')
            ->where('user_id', $user->getKey())
            ->where('category', $category->value)
            ->value('email');

        return $value === null ? true : (bool) $value;
    }

    /** @return array<string, bool> category value => email on or off */
    public function emailMap(User $user): array
    {
        $stored = DB::table('notification_preferences')
            ->where('user_id', $user->getKey())
            ->pluck('email', 'category');

        $map = [];

        foreach (NotificationCategory::cases() as $category) {
            $map[$category->value] = $category->isMandatory()
                ? true
                : (bool) ($stored[$category->value] ?? true);
        }

        return $map;
    }

    /**
     * Does this member want SMS for this group? Only groups that can send SMS say yes.
     * The default is "yes"; it still needs a verified number (SmsDispatcher checks that).
     * Payment SMS cannot be switched off, like payment email.
     */
    public function smsEnabled(User $user, NotificationCategory $category): bool
    {
        if (! $category->sendsSms()) {
            return false;
        }

        if ($category->isMandatory()) {
            return true;
        }

        $value = DB::table('notification_preferences')
            ->where('user_id', $user->getKey())
            ->where('category', $category->value)
            ->value('sms');

        return $value === null ? true : (bool) $value;
    }

    /** @return array<string, bool> category value => SMS on or off, for groups that can send SMS */
    public function smsMap(User $user): array
    {
        $stored = DB::table('notification_preferences')
            ->where('user_id', $user->getKey())
            ->pluck('sms', 'category');

        $map = [];

        foreach (NotificationCategory::smsGroups() as $category) {
            $map[$category->value] = $category->isMandatory()
                ? true
                : (bool) ($stored[$category->value] ?? true);
        }

        return $map;
    }

    /** Groups that cannot send SMS, and mandatory groups, are ignored. One atomic upsert. */
    public function setSms(User $user, NotificationCategory $category, bool $enabled): void
    {
        if (! $category->sendsSms() || $category->isMandatory()) {
            return;
        }

        DB::table('notification_preferences')->upsert(
            [[
                'user_id' => $user->getKey(),
                'category' => $category->value,
                'sms' => $enabled,
                'created_at' => now(),
                'updated_at' => now(),
            ]],
            ['user_id', 'category'],
            ['sms', 'updated_at'],
        );
    }

    /** Changing a mandatory group does nothing. One atomic upsert per group. */
    public function setEmail(User $user, NotificationCategory $category, bool $enabled): void
    {
        if ($category->isMandatory()) {
            return;
        }

        DB::table('notification_preferences')->upsert(
            [[
                'user_id' => $user->getKey(),
                'category' => $category->value,
                'email' => $enabled,
                'created_at' => now(),
                'updated_at' => now(),
            ]],
            ['user_id', 'category'],
            ['email', 'updated_at'],
        );
    }
}
