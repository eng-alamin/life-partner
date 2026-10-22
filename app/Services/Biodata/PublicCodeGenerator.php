<?php

declare(strict_types=1);

namespace App\Services\Biodata;

use App\Models\Biodata;

/**
 * Creates the random public identifier used in URLs (for example LP7K3M9QXA),
 * so numeric database IDs are never exposed. Letters that look alike
 * (I, O, 0, 1) are left out so the code is easy to read out loud.
 */
class PublicCodeGenerator
{
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const PREFIX = 'LP';

    private const RANDOM_LENGTH = 8;

    public function generate(): string
    {
        do {
            $code = self::PREFIX.$this->randomPart();
        } while (Biodata::withTrashed()->where('public_code', $code)->exists());

        return $code;
    }

    private function randomPart(): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $part = '';

        for ($i = 0; $i < self::RANDOM_LENGTH; $i++) {
            $part .= self::ALPHABET[random_int(0, $max)];
        }

        return $part;
    }
}
