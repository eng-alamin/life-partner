<?php

return [
    'title' => 'Download my data',
    'heading' => 'Download my data',
    'link' => 'Download my data',
    'back' => 'Back to my biodata',
    'intro' => 'You can download a copy of the data you gave us and the data your account created, as one file. The file is made when you ask and is not stored on our servers.',
    'includes' => [
        'heading' => 'What is in the file',
        'items' => [
            'Your account details (name, email, mobile number, language, dates).',
            'Your biodata with all its sections, your guardian details and your picture list (not the picture files).',
            'The interest requests you sent and received: only the other biodata\'s public code, the status and the dates, plus the message you wrote yourself.',
            'Your shortlist, ignore list and saved searches.',
            'Your payments, memberships, credit history and notification settings.',
            'The messages you sent through the contact form while signed in.',
        ],
    ],
    'excludes' => [
        'heading' => 'What is not in the file',
        'items' => [
            'Other members\' names, contact details, pictures or messages to you.',
            'Staff notes, review decisions and reports.',
            'Your password, sign-in codes and identity document files.',
        ],
    ],
    'keep_safe' => 'The file contains private details such as your phone number and guardian. Keep it somewhere safe and do not share it.',
    'proof' => [
        'password_label' => 'Your password',
        'email_label' => 'Your account email',
        'email_hint' => 'Your account has no password. Type your full account email address to continue.',
    ],
    'button' => 'Download my data',
    'errors' => [
        'password_wrong' => 'The password is not correct.',
        'email_wrong' => 'This is not the email address of your account.',
        'limit' => 'You can download your data :max times a day. Please try again tomorrow.',
    ],
];
