<?php

return [
    'status' => [
        'active' => 'Active',
        'suspended' => 'Suspended',
    ],

    'roles' => [
        'admin' => 'Admin',
        'moderator' => 'Moderator',
    ],

    'list' => [
        'title' => 'Users',
        'heading' => 'Users',
        'intro' => 'Find an account by name, email or biodata code. Phone numbers are not shown here.',
        'search_label' => 'Search',
        'search_placeholder' => 'Name, email or biodata code',
        'status_label' => 'Status',
        'role_label' => 'Type',
        'all' => 'All',
        'staff_only' => 'Staff only',
        'empty_title' => 'No accounts found',
        'empty_text' => 'Try another word or change the filters.',
        'email_unverified' => 'Email not verified',
        'biodata' => 'Biodata',
        'no_biodata' => 'none',
        'joined' => 'Joined: :date',
        'open' => 'Open',
        'previous' => 'Previous',
        'next' => 'Next',
    ],

    'show' => [
        'title' => 'Account',
        'back' => 'Back to the list',
        'intro' => 'Phone numbers and passwords are not shown here. You can only see whether the phone is verified.',
        'email' => 'Email',
        'status' => 'Status',
        'email_verified' => 'Email verified',
        'phone_verified' => 'Phone verified',
        'yes' => 'Yes',
        'no' => 'No',
        'joined' => 'Joined',
        'last_login' => 'Last login',
        'never' => 'Never',
        'biodata' => 'Biodata',
        'no_biodata' => 'No biodata yet',
        'view_biodata' => 'View biodata',
        'suspended_at' => 'Suspended on',
        'suspension_reason' => 'Reason for suspension',
        'self_note' => 'This is your own account. You can not suspend it or change its role here.',

        'suspend_heading' => 'Suspend this account',
        'suspend_text' => 'The member is signed out at once and can not log in. Their biodata disappears from search and profiles. The biodata is not deleted; it comes back when the account is reactivated.',
        'suspend_cancels' => 'Interest requests that are still waiting, sent or received, are cancelled at once and the other side is not told why. Accepted interests stay as they are. The member gets one email saying the account is suspended; the reason is not in it.',
        'reason_label' => 'Reason for suspension',
        'reason_hint' => 'Only staff can read this. The member is not shown it. 3 to 500 characters.',
        'suspend_button' => 'Suspend',
        'suspend_confirm' => 'Suspend this account? The member is signed out at once.',

        'reactivate_heading' => 'Reactivate this account',
        'reactivate_text' => 'The member can log in again and their approved biodata is visible again.',
        'reactivate_button' => 'Reactivate',
        'reactivate_confirm' => 'Reactivate this account?',

        'staff_heading' => 'Staff role',
        'admin_note' => 'The admin role can not be given or taken away on the website. Use the lifepartner:grant-staff command.',
        'moderator_grant_text' => 'A moderator can review biodatas and handle reports. A moderator can not see payments, plans, content or users.',
        'moderator_grant_button' => 'Make moderator',
        'moderator_grant_confirm' => 'Make this member a moderator?',
        'moderator_remove_text' => 'Taking the role away makes this person an ordinary member again.',
        'moderator_remove_button' => 'Remove moderator role',
        'moderator_remove_confirm' => 'Remove the moderator role?',
    ],

    'errors' => [
        'reason_invalid' => 'Write a reason (:min to :max characters).',
        'already_suspended' => 'This account is already suspended.',
        'already_active' => 'This account is already active.',
        'already_moderator' => 'This person is already a moderator.',
        'not_moderator' => 'This person is not a moderator.',
        'roles_missing' => 'The staff roles do not exist yet. Run php artisan db:seed --class=RolesAndPermissionsSeeder first.',
    ],

    'flash' => [
        'suspended' => 'The account was suspended.',
        'reactivated' => 'The account was reactivated.',
        'moderator_granted' => 'The moderator role was given.',
        'moderator_removed' => 'The moderator role was removed.',
    ],
];
