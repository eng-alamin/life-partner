<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Enums\NotificationType;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Route;

/**
 * Turns a stored row into words in the reader's current language and decides where
 * "Open" may lead. A staff link is given only to someone who still holds the permission.
 */
final class NotificationPresenter
{
    /** @return array{id: string, title: string, body: string, when: string, read: bool} */
    public function present(DatabaseNotification $notification): array
    {
        $type = $this->typeOf($notification);
        $params = $this->paramsOf($notification);

        return [
            'id' => (string) $notification->getKey(),
            'title' => $type !== null ? __('notifications.types.'.$type->langKey().'.title') : __('notifications.unknown.title'),
            'body' => $type !== null ? __('notifications.types.'.$type->langKey().'.body', $params) : __('notifications.unknown.body'),
            'when' => (string) $notification->created_at?->diffForHumans(),
            'read' => $notification->read_at !== null,
        ];
    }

    /** The address to open, or null when there is none or the member may not use it. */
    public function urlFor(DatabaseNotification $notification, User $user): ?string
    {
        $type = $this->typeOf($notification);

        if ($type === null || ! Route::has($type->routeName())) {
            return null;
        }

        $permission = $type->permission();

        if ($permission !== null && ! $user->can($permission->value)) {
            return null;
        }

        return route($type->routeName());
    }

    private function typeOf(DatabaseNotification $notification): ?NotificationType
    {
        $data = $notification->data;

        return is_array($data) ? NotificationType::tryFrom((string) ($data['type'] ?? '')) : null;
    }

    /** @return array<string, string> */
    private function paramsOf(DatabaseNotification $notification): array
    {
        $data = $notification->data;
        $params = is_array($data) && is_array($data['params'] ?? null) ? $data['params'] : [];
        $clean = [];

        foreach ($params as $key => $value) {
            if (is_scalar($value)) {
                $clean[(string) $key] = (string) $value;
            }
        }

        return $clean;
    }
}
