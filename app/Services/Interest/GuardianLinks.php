<?php

declare(strict_types=1);

namespace App\Services\Interest;

use App\Enums\GuardianApprovalStatus;
use App\Enums\InterestStatus;
use App\Models\Interest;

/**
 * Helpers for the one-time guardian link. The link token is random and shown
 * only once to the receiver. The database keeps only its SHA-256 hash.
 */
final class GuardianLinks
{
    public const TOKEN_LENGTH = 48;

    public function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public function isWellFormed(string $token): bool
    {
        return preg_match('/\A[A-Za-z0-9]{'.self::TOKEN_LENGTH.'}\z/', $token) === 1;
    }

    /**
     * The interest that this token can still decide, or null. A bad, used,
     * expired or unrelated token all give the same null, so nothing is revealed.
     */
    public function findOpen(string $token, bool $lock = false): ?Interest
    {
        if (! $this->isWellFormed($token)) {
            return null;
        }

        $query = Interest::query()
            ->with('sender.presentDistrict')
            ->where('guardian_token_hash', $this->hash($token));

        if ($lock) {
            $query->lockForUpdate();
        }

        $interest = $query->first();

        if ($interest === null
            || $interest->status !== InterestStatus::Accepted
            || $interest->guardian_status !== GuardianApprovalStatus::Pending
            || $interest->guardian_token_expires_at === null
            || $interest->guardian_token_expires_at->isPast()) {
            return null;
        }

        return $interest;
    }
}
