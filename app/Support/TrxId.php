<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Validation\ValidationException;

/**
 * Cleans a bKash TrxID typed by a person: Bangla digits become English, spaces are
 * removed, letters become upper case. A TrxID is 8 to 12 letters and digits.
 */
final class TrxId
{
    /** @throws ValidationException when something is typed but it is not a valid TrxID */
    public static function clean(?string $value): ?string
    {
        $text = strtoupper(preg_replace('/\s+/u', '', (string) Digits::normalize($value)) ?? '');

        if ($text === '') {
            return null;
        }

        if (preg_match('/^[A-Z0-9]{8,12}$/', $text) !== 1) {
            throw ValidationException::withMessages(['trx_id' => __('billing.errors.trx_invalid')]);
        }

        return $text;
    }
}
