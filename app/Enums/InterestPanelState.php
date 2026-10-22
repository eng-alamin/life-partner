<?php

declare(strict_types=1);

namespace App\Enums;

/** What the interest panel on a profile page shows to the signed-in member. */
enum InterestPanelState: string
{
    /** The member is looking at their own biodata. */
    case Own = 'own';

    /** The member has not started a biodata yet. */
    case NoBiodata = 'no_biodata';

    /** The member's biodata is not approved, or the account is not active. */
    case NotApproved = 'not_approved';

    /** An interest between the two was accepted. */
    case Connected = 'connected';

    /** The member already sent an interest that is still waiting. */
    case Sent = 'sent';

    /** The other member sent an interest that is still waiting. */
    case Received = 'received';

    /** The member may write a message and send an interest. */
    case CanSend = 'can_send';
}
