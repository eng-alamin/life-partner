<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * One notification class for every event. It carries a type and a few short codes
 * (for example the other person's biodata code) and nothing else.
 *
 * The Notifier sends it twice on purpose: once on the "database" channel right away
 * (so the bell is correct even when no queue worker runs) and once on the "mail"
 * channel through the queue (so a slow or failed mail never blocks the member).
 * Nothing here may contain a phone, email, address, guardian detail, TrxID,
 * payer number or photo.
 */
final class LifePartnerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> seconds */
    public array $backoff = [60, 300];

    /**
     * @param  array<string, string>  $params
     * @param  list<string>  $channels
     */
    public function __construct(
        public readonly NotificationType $type,
        public readonly array $params = [],
        private readonly array $channels = ['database'],
    ) {
        // Nothing is sent if the surrounding database transaction rolls back.
        $this->afterCommit();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    /** @return array{type: string, params: array<string, string>} */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => $this->type->value,
            'params' => $this->params,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $key = 'notifications.types.'.$this->type->langKey();
        $name = (string) ($notifiable->name ?? '');

        return (new MailMessage)
            ->subject(__('notifications.mail.subject', ['app' => config('app.name'), 'title' => __($key.'.title')]))
            ->greeting(__('notifications.mail.greeting', ['name' => $name]))
            ->line(__($key.'.body', $this->params))
            ->action(__('notifications.mail.action', ['app' => config('app.name')]), route($this->type->routeName()))
            ->line(__('notifications.mail.privacy'))
            ->salutation(config('app.name'));
    }
}
