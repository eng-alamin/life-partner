<?php

return [
    'errors' => [
        'not_waiting' => 'A guardian link cannot be created for this request now.',
        'invalid_link' => 'This link no longer works. It may have been used, it may have expired, or it is not correct. Please ask the member to create a new link.',
    ],

    'page' => [
        'title' => 'Guardian approval',
        'heading' => 'Guardian approval',
        'intro' => 'A Life Partner member has accepted the interest of the biodata below. Your approval is asked before any contact details are shared.',
        'note' => 'You can give your decision only once. If you approve, the guardian details may be shared. If you do not approve, the request is closed.',
        'approve' => 'I approve',
        'decline' => 'I do not approve',
        'confirm_decline' => 'Are you sure? This decision cannot be changed.',
        'done_approved' => 'Thank you. Your approval has been recorded. You can close this page now.',
        'done_declined' => 'Your decision has been recorded. You can close this page now.',
    ],

    'inbox' => [
        'create_link' => 'Create guardian link',
        'new_link' => 'Create a new link',
        'link_ready' => 'Send this link to your guardian. It is shown only once, so please copy it now.',
        'copy' => 'Copy link',
        'copied' => 'Copied',
        'close' => 'Close',
        'link_label' => 'Guardian link',
        'valid_until' => 'The link is valid until :date',
        'waiting_hint' => 'Create a link for your guardian approval and send it to them.',
        'link_valid_hint' => 'The earlier link is still valid. If you lost it, create a new one (the old one stops working).',
    ],
];
