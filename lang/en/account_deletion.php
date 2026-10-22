<?php

return [
    'title' => 'Delete my account',
    'heading' => 'Delete my account',
    'link' => 'Delete my account',
    'intro' => 'You can ask us to delete your account and your biodata. Nothing is deleted at once: you have a waiting period to change your mind.',
    'what_happens' => [
        'heading' => 'What happens',
        'wait' => 'Your data is deleted after :days days. Until then you can cancel.',
        'hidden' => 'Your biodata leaves search right away and your pending interest requests are cancelled. Nobody is told.',
        'cancel' => 'If you cancel, your biodata stays deactivated until you choose to reactivate it yourself.',
        'purged' => 'After the waiting period your biodata, photos, guardian details and other personal information are deleted for good and your login stops working.',
        'payments' => 'Records of payments and memberships are kept for accounting, without your name, email or phone number.',
    ],
    'start' => 'I want to delete my account',
    'confirm' => 'Yes, delete my account',
    'back' => 'Go back',
    'proof' => [
        'password_label' => 'Your password',
        'email_label' => 'Your email address',
        'email_hint' => 'Your account has no password, so type your email address in full to confirm.',
    ],
    'requested' => [
        'text' => 'Your account will be deleted on :date. Until then it is hidden from everyone else.',
        'cancel_help' => 'Changed your mind? Cancel now. Your biodata stays deactivated and you can reactivate it from "My biodata".',
        'cancel_button' => 'Cancel deletion',
    ],
    'staff' => 'This account has a staff role. Ask an administrator to remove the role first, then you can delete the account.',
    'errors' => [
        'password_wrong' => 'The password is not correct.',
        'email_wrong' => 'The email address does not match this account.',
        'already_requested' => 'You have already asked to delete this account.',
        'not_requested' => 'There is no deletion request to cancel.',
        'staff' => 'A staff account cannot be deleted until its role is removed.',
    ],
    'flash' => [
        'requested' => 'Your deletion request is saved. Your biodata is now hidden.',
        'cancelled' => 'Deletion cancelled. Your biodata is still deactivated: reactivate it when you are ready.',
    ],
];
