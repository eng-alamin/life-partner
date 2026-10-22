<?php

declare(strict_types=1);

namespace App\Actions\Interest;

use App\Actions\Credit\SpendCredit;
use App\Models\Biodata;
use App\Models\Interest;
use App\Models\User;
use App\Services\Interest\ContactReveal;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * A member asks to see the other side's guardian contact for one of their own
 * interests. The interest is looked up only among the member's own interests,
 * ContactReveal decides what may be shown, and every reveal is written to the
 * activity log with the interest code and side only (never the name or phone).
 * Showing the contact costs one credit per interest, once (see SpendCredit).
 */
final class RevealContact
{
    public function __construct(
        private readonly ContactReveal $contacts,
        private readonly SpendCredit $credits,
    ) {}

    /** @return array{name: string, relation: string, phone: string} */
    public function __invoke(User $user, string $code): array
    {
        $own = Biodata::query()->where('user_id', $user->getKey())->firstOrFail();

        $interest = Interest::query()
            ->with(['sender.guardian', 'receiver.guardian'])
            ->where('code', $code)
            ->where(function ($query) use ($own): void {
                $query->where('sender_biodata_id', $own->getKey())
                    ->orWhere('receiver_biodata_id', $own->getKey());
            })
            ->firstOrFail();

        Gate::forUser($user)->authorize('revealContact', [$interest, $own]);

        $details = $this->contacts->detailsFor($interest, $own);

        if ($details === null) {
            throw ValidationException::withMessages(['form' => __('contact.errors.not_available')]);
        }

        // Every check passed. Now the member pays one credit for this interest, once:
        // asking again for the same interest is free. Without a credit, nothing is shown.
        ($this->credits)($user, $interest);

        activity('interest')
            ->causedBy($user)
            ->performedOn($interest)
            ->withProperties([
                'interest_code' => $interest->code,
                'side' => (int) $own->getKey() === (int) $interest->sender_biodata_id ? 'sender' : 'receiver',
            ])
            ->log('interest.contact_revealed');

        return $details;
    }
}
