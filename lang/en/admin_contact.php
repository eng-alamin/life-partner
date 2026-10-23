<?php

return [
    'title' => 'Contact messages',
    'heading' => 'Contact messages',
    'intro' => 'Messages sent through the contact form. Reply from your own email program. Messages are deleted automatically after 180 days.',
    'tabs' => [
        'new' => 'New',
        'handled' => 'Handled',
        'spam' => 'Spam',
    ],
    'empty' => [
        'new' => ['title' => 'No new messages', 'text' => 'When someone writes through the contact form, the message appears here.'],
        'handled' => ['title' => 'Nothing handled yet', 'text' => 'Messages you mark as handled appear here.'],
        'spam' => ['title' => 'No spam', 'text' => 'Messages you mark as spam appear here.'],
    ],
    'list' => [
        'from' => 'From: :name',
        'sent' => 'Sent :time',
        'open_button' => 'Open',
        'previous' => 'Previous',
        'next' => 'Next',
    ],
    'review' => [
        'title' => 'Contact message',
        'back' => 'Back to the contact messages',
        'status' => 'Status',
        'sent' => 'Sent',
        'name' => 'Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'no_phone' => 'Not given',
        'sender' => 'Sent by',
        'guest' => 'A visitor (not signed in)',
        'member' => 'A signed-in member',
        'member_code' => 'A signed-in member (:code)',
        'message' => 'Message',
        'decided' => 'Decided on :date.',
        'reply' => 'Reply by email',
        'reply_help' => 'The reply button opens your email program. Nothing is sent from this page.',
        'mark_handled' => 'Mark as handled',
        'mark_spam' => 'Mark as spam',
        'reopen' => 'Open again',
    ],
    'flash' => [
        'handled' => 'The message is marked as handled.',
        'spam' => 'The message is marked as spam.',
        'reopened' => 'The message is open again.',
    ],
    'errors' => [
        'already' => 'The message already has this status.',
    ],
];
