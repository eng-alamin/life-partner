<?php

declare(strict_types=1);

namespace App\Enums;

/** In what form the consent was given. The proof is kept by the organisation, outside this system. */
enum StoryConsentForm: string
{
    case Written = 'written';
    case Message = 'message';
    case Phone = 'phone';
    case InPerson = 'in_person';

    public function label(): string
    {
        return __('enums.story_consent_form.'.$this->value);
    }
}
