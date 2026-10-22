<?php

declare(strict_types=1);

namespace App\Enums;

enum BloodGroup: string
{
    case APositive = 'a_pos';
    case ANegative = 'a_neg';
    case BPositive = 'b_pos';
    case BNegative = 'b_neg';
    case AbPositive = 'ab_pos';
    case AbNegative = 'ab_neg';
    case OPositive = 'o_pos';
    case ONegative = 'o_neg';

    /** Blood groups are written the same way in every language. */
    public function label(): string
    {
        return match ($this) {
            self::APositive => 'A+',
            self::ANegative => 'A-',
            self::BPositive => 'B+',
            self::BNegative => 'B-',
            self::AbPositive => 'AB+',
            self::AbNegative => 'AB-',
            self::OPositive => 'O+',
            self::ONegative => 'O-',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
