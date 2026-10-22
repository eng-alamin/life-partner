<?php

declare(strict_types=1);

namespace App\Actions\Phone;

use App\Enums\OtpPurpose;
use App\Models\OtpCode;
use App\Models\User;

/** Forgets the member's number and cancels any code still waiting for it. */
final class RemovePhone
{
    public function handle(User $user): void
    {
        $hadNumber = $user->phone !== null;

        $user->forceFill(['phone' => null, 'phone_hash' => null, 'phone_verified_at' => null])->save();

        OtpCode::query()
            ->where('user_id', $user->getKey())
            ->where('purpose', OtpPurpose::Phone->value)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        if ($hadNumber) {
            activity('account')->causedBy($user)->log('phone.removed');
        }
    }
}
