<?php

declare(strict_types=1);

namespace App\Enums;

/** Who gave the consent to share the story. */
enum StoryConsentGiver: string
{
    case Groom = 'groom';
    case Bride = 'bride';
    case Both = 'both';

    public function label(): string
    {
        return __('enums.story_consent_giver.'.$this->value);
    }
}
