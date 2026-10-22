<?php

return [

    // Verification code. Kept short: one SMS holds a limited number of characters.
    'otp' => ':app: your verification code is :code. Valid for :minutes minutes. Do not share it with anyone.',

    // Urgent messages only. They never hold a name, number, code or amount.
    'types' => [
        'interest_accepted' => ':app: your interest was accepted. Open the website to continue.',
        'interest_contact_ready' => ':app: contact step is complete. Open the website to continue.',
        'payment_approved' => ':app: your payment was approved. Open the website to see it.',
        'payment_rejected' => ':app: your payment could not be confirmed. Open the website to see why.',
    ],

    'errors' => [
        'invalid' => 'Please enter a valid Bangladeshi mobile number, for example 01712345678.',
        'unavailable' => 'This number cannot be used. Please try another number.',
        'try_later' => 'Too many tries. Please try again tomorrow.',
        'send_failed' => 'We could not send the SMS right now. Please try again in a few minutes.',
    ],

    'settings' => [
        'phone_heading' => 'Mobile number for SMS',
        'phone_intro' => 'Add and verify your number to get short SMS for urgent news only. Your number is kept private and is never shown to other members.',
        'not_available' => 'SMS is not switched on for this website yet.',
        'phone_label' => 'Mobile number',
        'phone_hint' => 'A Bangladeshi number, for example 01712345678.',
        'send_code' => 'Send code',
        'code_sent' => 'We sent a 6-digit code by SMS.',
        'code_intro' => 'Enter the code we sent to :number.',
        'code_hint' => 'Enter the 6-digit code from the SMS. It is valid for :minutes minutes.',
        'verify' => 'Verify',
        'resend' => 'Send a new code',
        'change_number' => 'Use another number',
        'verified' => 'Verified',
        'verified_notice' => 'Your mobile number is verified.',
        'removed_notice' => 'Your mobile number was removed.',
        'remove' => 'Remove number',
        'remove_confirm' => 'Remove this mobile number? You will stop getting SMS.',
        'sms_toggle' => 'Also send SMS',
        'sms_needs_number' => 'Verify your mobile number above to turn this on.',
        'sms_hint_interests' => 'Only when an interest is accepted or contact is ready.',
        'sms_hint_payments' => 'Only when a payment is approved or not confirmed. This message is required.',
        'privacy_note' => 'SMS never contains names, numbers or private details.',
    ],

];
