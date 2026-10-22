<?php

declare(strict_types=1);

namespace App\Rules;

use App\Support\Digits;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Blocks phone numbers, email addresses, links and chat-app names in free text,
 * so members cannot bypass the contact-request flow by writing contact details
 * into their biodata. It is a safety net, not a perfect filter: admins still
 * review every biodata.
 */
final class NoContactDetails implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        if ($this->containsContactDetails($value)) {
            $fail(__('biodata.errors.no_contact_details'));
        }
    }

    public function containsContactDetails(string $text): bool
    {
        $text = (string) Digits::normalize($text);

        // Nine or more digits in a row, allowing spaces, dots and dashes between them (phone numbers).
        if (preg_match('/(?:\d[\s.\-]*){9,}/u', $text) === 1) {
            return true;
        }

        if (str_contains($text, '@')) {
            return true;
        }

        if (preg_match('~(https?://|www\.|\.(com|net|org|info|xyz|me|bd)\b)~iu', $text) === 1) {
            return true;
        }

        // Whole words only, so a name such as "Timothy" is not mistaken for "imo".
        return preg_match('/(?<!\p{L})(whatsapp|imo|telegram|messenger|facebook|instagram|viber|হোয়াটসঅ্যাপ|ইমো|ফেসবুক)(?!\p{L})/iu', $text) === 1;
    }
}
