<?php

declare(strict_types=1);

namespace App\Services\Sms;

use App\Enums\NotificationType;
use App\Enums\SmsStatus;
use App\Jobs\SendSms;
use App\Models\SmsMessage;
use App\Models\User;
use App\Services\Notification\NotificationPreferences;

/**
 * Decides whether a notification also becomes an SMS, and queues it.
 *
 * An SMS is queued only when ALL of these are true: the type is on the short
 * "urgent" list, the member has a VERIFIED number, the member has not switched
 * SMS off for that group, the gateway can really send, and the member is under the
 * daily limit. The queued job carries only the id of a database row: the number and
 * the text are read and built when the job runs, so neither sits in the queue.
 */
final class SmsDispatcher
{
    public function __construct(
        private readonly SmsGateway $gateway,
        private readonly NotificationPreferences $preferences,
    ) {}

    public function send(User $user, NotificationType $type): void
    {
        if (! $type->sendsSms() || ! $user->hasVerifiedPhone() || ! $this->gateway->isReady()) {
            return;
        }

        if (! $this->preferences->smsEnabled($user, $type->category())) {
            return;
        }

        if ($this->overDailyLimit($user)) {
            return;
        }

        $message = SmsMessage::query()->create([
            'user_id' => $user->getKey(),
            'type' => $type->value,
            'status' => SmsStatus::Queued->value,
        ]);

        // Not sent if the surrounding database transaction rolls back.
        SendSms::dispatch((int) $message->getKey())->afterCommit();
    }

    /**
     * A cost guard, not a security boundary: two requests at the same moment could
     * both pass. 0 means "send no SMS at all".
     */
    private function overDailyLimit(User $user): bool
    {
        $limit = max(0, (int) config('lifepartner.sms.daily_cap_per_member'));

        if ($limit === 0) {
            return true;
        }

        $count = SmsMessage::query()
            ->where('user_id', $user->getKey())
            ->where('type', '!=', SmsMessage::TYPE_OTP)
            ->where('created_at', '>=', now()->subDay())
            ->whereIn('status', [SmsStatus::Queued->value, SmsStatus::Sending->value, SmsStatus::Sent->value])
            ->count();

        return $count >= $limit;
    }
}
