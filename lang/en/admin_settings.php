<?php

return [
    'title' => 'Site settings',
    'heading' => 'Site settings',
    'intro' => 'Only a few safe settings can be changed here. The bKash number, payments, SMS and privacy rules can not be changed from this page.',

    'registration' => [
        'heading' => 'New registrations',
        'label' => 'Allow new members to register',
        'hint' => 'When switched off, nobody new can open an account with email, Google or Facebook. People who already have an account can still log in. While registration is off, the register buttons and links disappear from the site.',
        'status_open' => 'Open now',
        'status_closed' => 'Closed now',
    ],

    'contact' => [
        'heading' => 'Contact details',
        'intro' => 'These details are shown to everyone on the contact page and at the bottom of every page (the footer). Leave a field empty to hide it. Give only office or support details, never a private number or a home address.',
        'email' => 'Email',
        'phone' => 'Phone',
        'phone_hint' => 'For example 01XXXXXXXXX or +8801XXXXXXXXX. A number from abroad is fine too.',
        'address_bn' => 'Address (Bangla)',
        'address_en' => 'Address (English)',
        'address_hint' => 'One line, at most :max characters. If the English address is empty, English readers see the Bangla one.',
    ],

    'save' => 'Save',

    'flash' => [
        'saved' => 'The settings have been saved.',
    ],

    'errors' => [
        'email_invalid' => 'Enter a valid email address (at most 120 characters).',
        'phone_invalid' => 'Enter a valid phone number (7 to 15 digits; it may start with +).',
        'address_invalid' => 'The address can be at most :max characters.',
    ],
];
