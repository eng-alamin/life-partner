<?php

declare(strict_types=1);

namespace App\Enums;

/** Why a member reports a biodata. Labels live in lang/{locale}/reports.php. */
enum ReportReason: string
{
    case FakeProfile = 'fake_profile';
    case WrongInformation = 'wrong_information';
    case InappropriateContent = 'inappropriate_content';
    case Harassment = 'harassment';
    case MoneyRequest = 'money_request';
    case Other = 'other';

    public function label(): string
    {
        return __('reports.reasons.'.$this->value);
    }

    /** "Other" needs a written explanation, the rest are self-explanatory. */
    public function needsDetails(): bool
    {
        return $this === self::Other;
    }

    /** @return array<string, string> value => translated label, in case order */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
