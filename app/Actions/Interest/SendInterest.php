<?php

declare(strict_types=1);

namespace App\Actions\Interest;

use App\Services\Notification\Notifier;
use App\Enums\GuardianApprovalStatus;
use App\Enums\InterestStatus;
use App\Models\Biodata;
use App\Models\Interest;
use App\Models\User;
use App\Rules\NoContactDetails;
use App\Services\Block\BlockGuard;
use App\Services\Interest\InterestTransitions;
use App\Services\Subscription\ActiveMembership;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Sends an interest from the signed-in member's biodata to another biodata.
 *
 * The receiver is found by its public code only (never a numeric id), and only
 * if it is approved. Every refusal about the receiver uses one message, so the
 * answer never tells the sender why a profile is unavailable.
 *
 * The sender's biodata row is locked while the rules run, so two quick taps
 * cannot slip past the daily limit. The unique "pending_pair" index in the
 * database is the last safety net against a duplicate open request.
 */
final class SendInterest
{
    public function __construct(
        private readonly InterestTransitions $transitions,
        private readonly NoContactDetails $contactRule,
        private readonly BlockGuard $blocks,
        private readonly ActiveMembership $membership,
        private readonly Notifier $notifier,
    ) {}

    public function __invoke(User $user, string $receiverCode, ?string $message = null): Interest
    {
        try {
            $interest = DB::transaction(fn (): Interest => $this->send($user, $receiverCode, $message));
        } catch (UniqueConstraintViolationException) {
            throw $this->error('already_open');
        }

        // After the work is saved. A notification problem never undoes the interest.
        $this->notifier->interestSent($interest);

        return $interest;
    }

    private function send(User $user, string $receiverCode, ?string $message): Interest
    {
        $sender = Biodata::query()
            ->where('user_id', $user->getKey())
            ->lockForUpdate()
            ->first();

        if ($sender === null) {
            throw $this->error('no_biodata');
        }

        Gate::forUser($user)->authorize('send', [Interest::class, $sender]);

        $receiver = Biodata::query()
            ->searchable()
            ->with('guardian')
            ->where('public_code', $receiverCode)
            ->first();

        if (
            $receiver === null
            || $receiver->user_id === $sender->user_id
            || $receiver->type !== $sender->type?->opposite()
            || $this->blocks->between((int) $sender->user_id, (int) $receiver->user_id)
        ) {
            throw $this->error('receiver_unavailable');
        }

        $note = $this->cleanMessage($message);

        $this->assertNoBlockingHistory($sender, $receiver);
        $this->assertWithinDailyLimit($sender);

        $interest = new Interest;
        $interest->forceFill([
            'sender_biodata_id' => $sender->getKey(),
            'receiver_biodata_id' => $receiver->getKey(),
            'message' => $note,
            'guardian_status' => $this->guardianStatusFor($receiver)->value,
            'expires_at' => now()->addDays((int) config('lifepartner.interests.expiry_days')),
        ])->save();

        $this->transitions->recordCreated($interest, $user);

        return $interest;
    }

    /** Trims the note and refuses contact details or an over-long text. */
    private function cleanMessage(?string $message): ?string
    {
        $note = trim((string) $message);

        if ($note === '') {
            return null;
        }

        if (mb_strlen($note) > (int) config('lifepartner.interests.message_max')) {
            throw $this->error('message_too_long', 'message', ['max' => (int) config('lifepartner.interests.message_max')]);
        }

        if ($this->contactRule->containsContactDetails($note)) {
            throw $this->error('message_contact', 'message');
        }

        return $note;
    }

    /** Looks at every earlier request between the two biodatas, in both directions. */
    private function assertNoBlockingHistory(Biodata $sender, Biodata $receiver): void
    {
        $earlier = Interest::query()
            ->where(function ($query) use ($sender, $receiver): void {
                $query->where(fn ($q) => $q->where('sender_biodata_id', $sender->getKey())->where('receiver_biodata_id', $receiver->getKey()))
                    ->orWhere(fn ($q) => $q->where('sender_biodata_id', $receiver->getKey())->where('receiver_biodata_id', $sender->getKey()));
            })
            ->whereIn('status', [InterestStatus::Pending->value, InterestStatus::Accepted->value, InterestStatus::Declined->value])
            ->get();

        $cooldownDays = (int) config('lifepartner.interests.decline_cooldown_days');

        foreach ($earlier as $row) {
            $mine = $row->sender_biodata_id === $sender->getKey();

            if ($row->status === InterestStatus::Accepted) {
                throw $this->error('already_connected');
            }

            if ($row->status === InterestStatus::Pending) {
                throw $this->error($mine ? 'already_open' : 'reverse_pending');
            }

            if ($mine && $row->status === InterestStatus::Declined && $row->responded_at !== null) {
                $until = $row->responded_at->copy()->addDays($cooldownDays);

                if ($until->isFuture()) {
                    throw $this->error('cooldown', 'form', [
                        'days' => max(1, (int) ceil(now()->diffInDays($until, true))),
                    ]);
                }
            }
        }
    }

    /** Rolling 24 hours. Cancelled requests still count, so cancel-and-resend cannot dodge the limit. */
    private function assertWithinDailyLimit(Biodata $sender): void
    {
        // The free limit, or the higher limit of the member's current membership.
        $limit = $this->membership->dailyInterestLimit((int) $sender->user_id);

        $sent = Interest::query()
            ->where('sender_biodata_id', $sender->getKey())
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($sent >= $limit) {
            throw $this->error('daily_limit', 'form', ['limit' => $limit]);
        }
    }

    /** The receiver's guardian must approve only if the setting is on and a guardian can approve. */
    private function guardianStatusFor(Biodata $receiver): GuardianApprovalStatus
    {
        $required = (bool) config('lifepartner.interests.guardian_approval')
            && $receiver->guardian?->can_approve === true;

        return $required ? GuardianApprovalStatus::Pending : GuardianApprovalStatus::NotRequired;
    }

    /** @param  array<string, int|string>  $replace */
    private function error(string $key, string $field = 'form', array $replace = []): ValidationException
    {
        return ValidationException::withMessages([$field => __('interests.errors.'.$key, $replace)]);
    }
}
