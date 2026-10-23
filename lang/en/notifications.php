<?php

return [

    'categories' => [
        'interests' => 'Interests and contact',
        'profile' => 'Biodata and photos',
        'payments' => 'Payments',
        'staff' => 'Staff alerts',
    ],

    'category_hints' => [
        'interests' => 'When someone sends, accepts, declines or withdraws an interest, and when contact is ready.',
        'profile' => 'When your biodata or a photo is approved or rejected.',
        'payments' => 'When a payment is approved, rejected or refunded, and when your membership ends soon or has ended.',
        'staff' => 'When a payment, biodata, photo or report is waiting for you.',
    ],

    'page' => [
        'title' => 'Notifications',
        'heading' => 'Notifications',
        'intro' => 'What happened on your account. No phone numbers or private details are shown here.',
        'filter_all' => 'All',
        'filter_unread' => 'Unread',
        'mark_all_read' => 'Mark all as read',
        'mark_read' => 'Mark as read',
        'open' => 'Open',
        'new' => 'New',
        'settings_link' => 'Email settings',
        'empty_title' => 'No notifications yet',
        'empty_text' => 'When something happens on your account, you will see it here.',
        'empty_unread_title' => 'Nothing unread',
        'empty_unread_text' => 'You have read everything.',
        'previous' => 'Previous',
        'next' => 'Next',
    ],

    'settings' => [
        'title' => 'Notification settings',
        'heading' => 'Notification settings',
        'intro' => 'Choose which messages we also send by email.',
        'mandatory' => 'This message is required and cannot be switched off.',
        'in_app_note' => 'The list inside the website always stays on.',
        'save' => 'Save',
        'back' => 'Back to notifications',
        'saved' => 'Your settings were saved.',
    ],

    'mail' => [
        'subject' => ':title - :app',
        'greeting' => 'Assalamu alaikum, :name',
        'action' => 'Open :app',
        'privacy' => 'For your safety this message never contains phone numbers or private details. Please log in to see more.',
    ],

    'unknown' => [
        'title' => 'Notification',
        'body' => 'Something happened on your account.',
    ],

    'types' => [
        'interest_received' => [
            'title' => 'New interest received',
            'body' => 'Biodata :biodata sent you an interest. Open your interests to answer it.',
        ],
        'interest_accepted' => [
            'title' => 'Your interest was accepted',
            'body' => 'Biodata :biodata accepted your interest.',
        ],
        'interest_declined' => [
            'title' => 'Your interest was not accepted',
            'body' => 'Your interest to biodata :biodata was not accepted.',
        ],
        'interest_cancelled' => [
            'title' => 'An interest was withdrawn',
            'body' => 'Biodata :biodata withdrew its interest.',
        ],
        'interest_contact_ready' => [
            'title' => 'Contact is ready',
            'body' => 'The contact step with biodata :biodata is complete. Open your interests to continue.',
        ],
        'biodata_approved' => [
            'title' => 'Your biodata was approved',
            'body' => 'Your biodata is now visible to other members.',
        ],
        'biodata_rejected' => [
            'title' => 'Your biodata was not approved',
            'body' => 'Please open your biodata, read the note, fix it and send it again.',
        ],
        'photo_approved' => [
            'title' => 'Your photo was approved',
            'body' => 'Your photo passed the check.',
        ],
        'photo_rejected' => [
            'title' => 'Your photo was not approved',
            'body' => 'Please open your biodata to see the note and upload another photo.',
        ],
        'payment_approved' => [
            'title' => 'Your payment was approved',
            'body' => 'Your payment for :plan was confirmed. Open your payments page to see what you received.',
        ],
        'payment_rejected' => [
            'title' => 'Your payment was not approved',
            'body' => 'Your payment for :plan could not be confirmed. Please see the reason on your payments page.',
        ],
        'payment_refunded' => [
            'title' => 'Your payment was refunded',
            'body' => 'Your payment for :plan was marked as refunded. Open your payments page for details.',
        ],
        'membership_expiring' => [
            'title' => 'Your membership ends soon',
            'body' => 'Your :plan membership ends in about :days day(s). Open your payments page to renew and keep your benefits.',
        ],
        'membership_expired' => [
            'title' => 'Your membership has ended',
            'body' => 'Your :plan membership has ended. Open your payments page to renew.',
        ],
        'staff_biodata_changed' => [
            'title' => 'A biodata was changed',
            'body' => 'A member changed an approved biodata. It is waiting for your review again.',
        ],
        'staff_payment_waiting' => [
            'title' => 'A payment is waiting',
            'body' => 'A payment is waiting for your review.',
        ],
        'staff_biodata_waiting' => [
            'title' => 'A biodata is waiting',
            'body' => 'A biodata is waiting for your review.',
        ],
        'staff_photo_waiting' => [
            'title' => 'A photo is waiting',
            'body' => 'A photo is waiting for your review.',
        ],
        'staff_report_new' => [
            'title' => 'A new report',
            'body' => 'A new report is waiting for you.',
        ],
        'identity_approved' => [
            'title' => 'Your identity is verified',
            'body' => 'The "identity verified" badge is now shown on your biodata.',
        ],
        'identity_rejected' => [
            'title' => 'Your identity request was not accepted',
            'body' => 'Open the verification page to see the reason and send a new picture.',
        ],
        'staff_identity_waiting' => [
            'title' => 'An identity request is waiting',
            'body' => 'An identity request is waiting for your review.',
        ],
        'staff_contact_new' => [
            'title' => 'A new contact message',
            'body' => 'A new message is waiting in the contact inbox.',
        ],
    ],

];
