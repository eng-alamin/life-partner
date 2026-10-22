<?php

return [
    'alt' => 'Photo of the member',
    'heading' => 'Photos',
    'intro' => 'You can add up to :max photos. Every photo is checked by our team before anyone else can see it. Photos are never shown to guests.',
    'none' => 'You have not added a photo yet.',
    'main' => 'Main photo',
    'make_main' => 'Make main',
    'delete' => 'Delete',
    'confirm_delete' => 'Delete this photo?',
    'removed' => 'This photo was removed',
    'reason' => 'Reason:',
    'choose' => 'Choose a photo',
    'rules' => 'JPG, PNG or WebP, up to 5 MB, at least 300 by 300 pixels.',
    'upload' => 'Upload photo',
    'full' => 'You already have :max photos. Delete one to add another.',
    'uploaded' => 'Your photo was uploaded. It will be visible to others after our team approves it.',
    'deleted' => 'The photo was deleted.',
    'main_set' => 'The main photo was changed.',

    'visibility_label' => 'Who can see my approved photos',
    'visibility_hint' => 'You can change this at any time. Your photos stay hidden until our team approves them.',
    'visibility_save' => 'Save',
    'visibility_saved' => 'Your photo setting was saved.',

    'profile_heading' => 'Photos',

    'errors' => [
        'required' => 'Please choose a photo first.',
        'type' => 'Only JPG, PNG or WebP photos are allowed.',
        'too_big' => 'The photo must be 5 MB or smaller.',
        'dimensions' => 'The photo must be between 300 and 5000 pixels on each side.',
        'unreadable' => 'This file could not be read as a photo. Please choose another photo.',
        'too_large_pixels' => 'This photo is too large. Please choose a smaller one.',
        'upload_failed' => 'The upload failed. Please try again.',
        'max_photos' => 'You can add up to :max photos.',
        'rejected_main' => 'A rejected photo cannot be the main photo.',
        'visibility_invalid' => 'Please choose a valid option.',
    ],

    'admin' => [
        'title' => 'Photo review',
        'heading' => 'Photo review',
        'intro' => 'Photos waiting for approval, oldest first. Approve only clear photos of the member that follow our modesty rules.',
        'empty_title' => 'No photos to review',
        'empty_text' => 'New photos will appear here.',
        'alt' => 'Photo waiting for review',
        'approve' => 'Approve',
        'reject' => 'Reject',
        'reason_label' => 'Reason for rejecting (10 to 200 characters)',
        'approved' => 'The photo was approved.',
        'rejected' => 'The photo was rejected and its files were deleted.',
        'errors' => [
            'not_pending' => 'This photo is no longer waiting for review.',
            'reason_invalid' => 'Please write a reason of 10 to 200 characters.',
        ],
    ],
];
