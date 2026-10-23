<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Enums\BiodataStatus;
use App\Enums\GuardianApprovalStatus;
use App\Enums\InterestStatus;
use App\Enums\NotificationType;
use App\Models\Biodata;
use App\Models\BiodataPhoto;
use App\Models\Interest;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\LifePartnerNotification;
use App\Services\Sms\SmsDispatcher;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * The one place Actions call to tell people what happened. Call it AFTER the main
 * work succeeded. A notification problem is reported to the log and never breaks the
 * action that already finished.
 *
 * Only a type and short codes are stored or mailed. A few urgent types may also be
 * queued as an SMS by SmsDispatcher. Deactivated or suspended accounts
 * get nothing. Mail goes through the queue in the receiver's own language.
 */
final class Notifier
{
    public function __construct(
        private readonly NotificationPreferences $preferences,
        private readonly SmsDispatcher $sms,
    ) {}

    /** A new interest arrived: tell the receiver, naming the sender's biodata code. */
    public function interestSent(Interest $interest): void
    {
        $this->safely(function () use ($interest): void {
            $sides = $this->sides($interest);

            $this->toUser($sides['receiver']['user_id'], NotificationType::InterestReceived, ['biodata' => $sides['sender']['code']]);
        });
    }

    /** The receiver accepted or declined. Accepting with no guardian step left opens contact for both. */
    public function interestAnswered(Interest $interest): void
    {
        $this->safely(function () use ($interest): void {
            $sides = $this->sides($interest);

            if ($interest->status === InterestStatus::Accepted) {
                $this->toUser($sides['sender']['user_id'], NotificationType::InterestAccepted, ['biodata' => $sides['receiver']['code']]);
                $this->contactReady($interest, $sides);
            } elseif ($interest->status === InterestStatus::Declined) {
                $this->toUser($sides['sender']['user_id'], NotificationType::InterestDeclined, ['biodata' => $sides['receiver']['code']]);
            }
        });
    }

    /** The sender withdrew a pending interest: tell the receiver. */
    public function interestCancelled(Interest $interest): void
    {
        $this->safely(function () use ($interest): void {
            $sides = $this->sides($interest);

            $this->toUser($sides['receiver']['user_id'], NotificationType::InterestCancelled, ['biodata' => $sides['sender']['code']]);
        });
    }

    /**
     * The receiver's guardian decided through the one-time link. A decline closes the
     * interest, so the sender is told it was declined (the guardian is never named).
     * An approval matters only once the interest is also accepted.
     */
    public function guardianDecided(Interest $interest): void
    {
        $this->safely(function () use ($interest): void {
            $sides = $this->sides($interest);

            if ($interest->status === InterestStatus::Declined) {
                $this->toUser($sides['sender']['user_id'], NotificationType::InterestDeclined, ['biodata' => $sides['receiver']['code']]);

                return;
            }

            $this->contactReady($interest, $sides);
        });
    }

    public function biodataReviewed(Biodata $biodata): void
    {
        $type = match ($biodata->status) {
            BiodataStatus::Approved => NotificationType::BiodataApproved,
            BiodataStatus::Rejected => NotificationType::BiodataRejected,
            default => null,
        };

        if ($type !== null) {
            $this->toUser((int) $biodata->user_id, $type);
        }
    }

    /** The photo needs its biodata loaded (or the owner is looked up by query). */
    public function photoReviewed(BiodataPhoto $photo, NotificationType $type): void
    {
        $this->safely(function () use ($photo, $type): void {
            $ownerId = Biodata::query()->whereKey($photo->biodata_id)->value('user_id');

            if ($ownerId !== null) {
                $this->toUser((int) $ownerId, $type);
            }
        });
    }

    /** Payment approved, rejected or refunded. Names the plan only, never the TrxID or number. */
    public function paymentDecided(Payment $payment, NotificationType $type): void
    {
        if ($payment->user_id === null) {
            return;
        }

        $this->toUser((int) $payment->user_id, $type, ['plan' => (string) $payment->plan_name]);
    }

    /** A paid period ends in about this many days. Names the plan and the days, nothing else. */
    public function membershipEnding(Subscription $subscription, int $days): void
    {
        $this->toUser((int) $subscription->user_id, NotificationType::MembershipExpiring, [
            'plan' => (string) $subscription->plan_name,
            'days' => (string) max(1, $days),
        ]);
    }

    /** A paid period has ended and no other one continues. */
    public function membershipEnded(Subscription $subscription): void
    {
        $this->toUser((int) $subscription->user_id, NotificationType::MembershipExpired, [
            'plan' => (string) $subscription->plan_name,
        ]);
    }

    /** Something now waits for staff. Everyone holding the type's permission is told. */
    public function toStaff(NotificationType $type): void
    {
        $this->safely(function () use ($type): void {
            $permission = $type->permission();

            if ($permission === null) {
                return;
            }

            $name = $permission->value;

            // A plain query on the role and permission tables: it never fails when a
            // permission has not been created yet.
            $staff = User::query()
                ->where(function ($query) use ($name): void {
                    $query->whereHas('roles.permissions', fn ($p) => $p->where('name', $name))
                        ->orWhereHas('permissions', fn ($p) => $p->where('name', $name));
                })
                ->get();

            foreach ($staff as $user) {
                $this->deliver($user, $type, []);
            }
        });
    }

    /**
     * @param  array<string, scalar|null>  $params
     */
    public function toUser(int $userId, NotificationType $type, array $params = []): void
    {
        $this->safely(function () use ($userId, $type, $params): void {
            $user = User::query()->find($userId);

            if ($user !== null) {
                $this->deliver($user, $type, $params);
            }
        });
    }

    /** @param  array<string, scalar|null>  $params */
    private function deliver(User $user, NotificationType $type, array $params): void
    {
        if (! $user->isActive()) {
            return;
        }

        $clean = $this->cleanParams($params);

        // In-app row: written now, so the bell is right even without a queue worker.
        Notification::sendNow($user, new LifePartnerNotification($type, $clean, ['database']));

        // SMS is independent of email: a member with no email can still get one.
        // A problem here must never stop the email below.
        try {
            $this->sms->send($user, $type);
        } catch (Throwable $exception) {
            report($exception);
        }

        if (blank($user->email) || ! $this->preferences->emailEnabled($user, $type->category())) {
            return;
        }

        $locale = array_key_exists((string) $user->locale, (array) config('lifepartner.locales'))
            ? (string) $user->locale
            : (string) config('lifepartner.default_locale');

        $user->notify((new LifePartnerNotification($type, $clean, ['mail']))->locale($locale));
    }

    /**
     * Contact is ready only when the interest is accepted AND no guardian step is left.
     *
     * @param  array{sender: array{user_id: int, code: string}, receiver: array{user_id: int, code: string}}  $sides
     */
    private function contactReady(Interest $interest, array $sides): void
    {
        $open = $interest->status === InterestStatus::Accepted
            && in_array($interest->guardian_status, [GuardianApprovalStatus::NotRequired, GuardianApprovalStatus::Approved], true);

        if (! $open) {
            return;
        }

        $this->toUser($sides['sender']['user_id'], NotificationType::ContactReady, ['biodata' => $sides['receiver']['code']]);
        $this->toUser($sides['receiver']['user_id'], NotificationType::ContactReady, ['biodata' => $sides['sender']['code']]);
    }

    /**
     * Owners and public codes of both biodatas, read with one query (lazy loading is off).
     *
     * @return array{sender: array{user_id: int, code: string}, receiver: array{user_id: int, code: string}}
     */
    private function sides(Interest $interest): array
    {
        $rows = Biodata::query()
            ->whereIn('id', [$interest->sender_biodata_id, $interest->receiver_biodata_id])
            ->get(['id', 'user_id', 'public_code'])
            ->keyBy('id');

        $side = static function (int $id) use ($rows): array {
            $row = $rows->get($id);

            return ['user_id' => (int) ($row?->user_id ?? 0), 'code' => (string) ($row?->public_code ?? '')];
        };

        return [
            'sender' => $side((int) $interest->sender_biodata_id),
            'receiver' => $side((int) $interest->receiver_biodata_id),
        ];
    }

    /**
     * Only short plain strings are kept.
     *
     * @param  array<string, scalar|null>  $params
     * @return array<string, string>
     */
    private function cleanParams(array $params): array
    {
        $clean = [];

        foreach ($params as $key => $value) {
            if (is_scalar($value) && preg_match('/^[a-z_]{1,20}$/', (string) $key) === 1) {
                $clean[(string) $key] = mb_substr(trim((string) $value), 0, 60);
            }
        }

        return $clean;
    }

    private function safely(callable $work): void
    {
        try {
            $work();
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
