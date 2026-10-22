<?php

declare(strict_types=1);

namespace App\Enums;

/** The documents we accept. We keep a picture only, never the document number. */
enum IdentityDocumentType: string
{
    case Nid = 'nid';
    case Passport = 'passport';
    case DrivingLicense = 'driving_license';

    public function label(): string
    {
        return __('enums.identity_document_type.'.$this->value);
    }

    /** A passport has the photo page only. The other two documents may also have a back side. */
    public function hasBackSide(): bool
    {
        return $this !== self::Passport;
    }
}
