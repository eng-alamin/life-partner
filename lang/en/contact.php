<?php

return [
    'heading' => 'Write to us',
    'intro' => 'Have a question or a problem? Send us a message and we will answer by email.',
    'fields' => [
        'name' => 'Your name',
        'email' => 'Your email address',
        'phone' => 'Phone number',
        'subject' => 'Subject',
        'message' => 'Your message',
    ],
    'hints' => [
        'phone' => 'Optional. Only if you want us to call you.',
    ],
    'honeypot' => 'Leave this field empty',
    'send' => 'Send message',
    'sent' => 'Thank you. Your message has been sent. We will reply by email.',
    'privacy_note' => 'Please do not send passwords or card numbers. Your message is kept for :days days and then deleted.',
    'errors' => [
        'too_fast' => 'Please take a moment to check your message and press Send again.',
        'links' => 'Please use at most :max web addresses in your message.',
    ],
    'status' => [
        'new' => 'New',
        'handled' => 'Handled',
        'spam' => 'Spam',
    ],
];
