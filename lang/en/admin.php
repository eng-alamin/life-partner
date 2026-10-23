<?php

return [
    'dashboard' => [
        'title' => 'Admin dashboard',
        'heading' => 'Admin dashboard',
        'intro' => 'A quick look at Life Partner.',
        'open_queue' => 'Open the approval queue',
        'stats' => [
            'pending' => 'Waiting for review',
            'approved' => 'Approved biodatas',
            'rejected' => 'Rejected biodatas',
            'drafts' => 'Drafts',
            'members' => 'Members',
        ],
    ],

    'waiting' => [
        'heading' => 'Waiting for you',
        'intro' => 'Only what you are allowed to handle is shown.',
        'none' => 'Nothing is waiting for you right now.',
        'open' => 'Open',
        'badge_sr' => ':count waiting',
        'items' => [
            'biodata' => 'Biodatas to review',
            'photos' => 'Photos to review',
            'payments' => 'Payments to check',
            'payments_attention' => 'bKash payments needing attention',
            'reports' => 'Open reports',
            'identity' => 'Identity requests',
        ],
    ],

    'queue' => [
        'title' => 'Approval queue',
        'heading' => 'Approval queue',
        'intro' => 'Biodatas waiting for review, oldest first.',
        'empty_title' => 'Nothing to review',
        'empty_text' => 'New and changed biodatas will appear here.',
        'age' => ':age years',
        'submitted' => 'Sent :time',
        'review' => 'Review',
        'previous' => 'Previous',
        'next' => 'Next',
    ],

    'review' => [
        'title' => 'Review biodata',
        'back' => 'Back to the queue',
        'intro' => 'Check the details below. Guardian name and phone, and the member email, are not shown here.',
        'none' => '—',
        'yes' => 'Yes',
        'no' => 'No',
        'age_range' => 'Preferred age',
        'decision' => 'Your decision',
        'approve' => 'Approve',
        'reject' => 'Reject',
        'reason_label' => 'Reason for rejection',
        'reason_hint' => 'The member will read this. Say clearly what to fix (10 to 500 characters).',
        'stale' => 'The member changed this biodata after you opened it. Load the latest version before you decide.',
        'reload' => 'Load the latest version',
        'not_pending' => 'This biodata is not waiting for review, so no decision can be made.',
    ],

    'flash' => [
        'approved' => 'The biodata was approved.',
        'rejected' => 'The biodata was rejected and the member can fix it and send it again.',
    ],

    'errors' => [
        'not_pending' => 'This biodata is no longer waiting for review.',
        'changed' => 'The member changed this biodata after you opened it. Load the latest version and review it again.',
        'reason_invalid' => 'Please write a reason of up to 500 characters.',
    ],
];
