<?php

return [
    'hide' => [
        'button' => 'Hide my biodata',
        'help' => 'While hidden, your biodata does not appear in search or to other members, and your pending interest requests are cancelled. You can show it again at any time.',
    ],
    'unhide' => [
        'button' => 'Show again',
        'help_same' => 'You have not changed anything since hiding it, so it returns to search at once.',
        'help_changed' => 'You changed your biodata while it was hidden, so it goes back to review and returns to search once it is approved.',
    ],
    'deactivate' => [
        'button' => 'Deactivate my biodata',
        'help' => 'While deactivated, your biodata does not appear in search or to other members, your pending interest requests are cancelled and you can not edit it. You can reactivate it at any time.',
        'reason_label' => 'Reason (optional)',
        'reason_none' => 'I prefer not to say',
        'reason_invalid' => 'Choose a reason from the list.',
        'confirm' => 'Deactivate',
        'cancel' => 'Cancel',
        'reasons' => [
            'found_here' => 'I found a partner here',
            'found_elsewhere' => 'I found a partner elsewhere',
            'break' => 'I am taking a break',
            'other' => 'Another reason',
        ],
    ],
    'reactivate' => [
        'button' => 'Reactivate',
        'help_same' => 'Nothing changed since you paused it, so it returns to search at once.',
        'help_changed' => 'It goes back to the approval queue and appears in search once it is approved.',
    ],
    'staff_hidden' => 'Our team has hidden your biodata. You can not show it again yourself. Please contact us if you need help.',
    'flash' => [
        'deactivated' => 'Your biodata is now deactivated.',
        'reactivated' => 'Your biodata is active again and visible in search.',
        'hidden' => 'Your biodata is now hidden.',
        'shown' => 'Your biodata is visible in search again.',
        'sent_for_review' => 'Your biodata was sent for review. It will be visible again once it is approved.',
    ],
];
