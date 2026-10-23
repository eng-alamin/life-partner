<?php

declare(strict_types=1);

namespace App\Enums;

/** Whether a success story is on the public page. */
enum StoryStatus: string
{
    case Draft = 'draft';
    case Published = 'published';

    public function label(): string
    {
        return __('enums.story_status.'.$this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Published => 'brand',
            self::Draft => 'stone',
        };
    }
}
