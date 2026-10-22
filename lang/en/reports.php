<?php

return [
    'errors' => [
        'unavailable' => 'This biodata is not available right now.',
        'already_reported' => 'You have already reported this biodata. Our team will review it.',
        'reason_required' => 'Please choose a reason.',
        'details_required' => 'Please describe the problem in a few words.',
        'details_too_long' => 'Please keep the description within :max characters.',
        'daily_limit' => 'You can send up to :limit reports in 24 hours. Please try again later.',
    ],

    'reasons' => [
        'fake_profile' => 'Fake or untrue profile',
        'wrong_information' => 'Wrong or misleading information',
        'inappropriate_content' => 'Inappropriate photo or text',
        'harassment' => 'Harassment or unwanted contact',
        'money_request' => 'Asked for money or gifts',
        'other' => 'Something else',
    ],

    'status' => [
        'open' => 'Open',
        'dismissed' => 'Dismissed',
        'resolved' => 'Resolved',
    ],

    'ui' => [
        'open_button' => 'Report this profile',
        'help' => 'Reports are private. The member is not told.',
        'heading' => 'Report this profile',
        'intro' => 'Tell us what is wrong. Our team will look into it. Please do not share phone numbers or private details of other people.',
        'reason_label' => 'Reason',
        'choose_reason' => 'Choose a reason',
        'details_label' => 'What happened? (optional)',
        'details_hint' => 'Up to :max characters. Required if you choose "Something else".',
        'submit' => 'Send report',
        'cancel' => 'Cancel',
        'sent' => 'Thank you. Your report was sent to our team.',
        'already_reported' => 'You have reported this profile. Our team will review it.',
    ],
];
