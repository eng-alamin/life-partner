<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supported locales
    |--------------------------------------------------------------------------
    | Bangla is the default and a first-class language. Keys are locale codes,
    | values are the native names shown in the language switcher.
    */
    'locales' => [
        'bn' => 'বাংলা',
        'en' => 'English',
    ],

    'default_locale' => env('APP_LOCALE', 'bn'),

    'locale_cookie' => 'lp_locale',

    'locale_cookie_minutes' => 60 * 24 * 365,

    /*
    |--------------------------------------------------------------------------
    | Interest requests
    |--------------------------------------------------------------------------
    | daily_limit: new interests one member may send per rolling 24 hours (free plan).
    | expiry_days: a pending request expires after this many days.
    | decline_cooldown_days: the sender cannot ask the same person again for this long
    |   after a decline.
    | guardian_link_days: how long the one-time guardian link stays valid.
    | guardian_approval: when true, the receiver's guardian must approve before
    |   contact details are shared. (Becomes an admin setting in Module 10.)
    */
    'interests' => [
        'daily_limit' => (int) env('LP_INTEREST_DAILY_LIMIT', 5),
        'expiry_days' => (int) env('LP_INTEREST_EXPIRY_DAYS', 14),
        'decline_cooldown_days' => (int) env('LP_INTEREST_DECLINE_COOLDOWN_DAYS', 30),
        'message_max' => 300,
        'guardian_link_days' => (int) env('LP_GUARDIAN_LINK_DAYS', 7),
        'guardian_approval' => (bool) env('LP_GUARDIAN_APPROVAL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Blocking
    |--------------------------------------------------------------------------
    | max: how many members one person may block.
    */
    'blocks' => [
        'max' => (int) env('LP_BLOCK_MAX', 200),
    ],

    /*
    |--------------------------------------------------------------------------
    | Shortlist and ignore list
    |--------------------------------------------------------------------------
    | shortlist_max / ignore_max: how many biodatas one member may keep on each list.
    */
    'lists' => [
        'shortlist_max' => (int) env('LP_SHORTLIST_MAX', 200),
        'ignore_max' => (int) env('LP_IGNORE_MAX', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    | daily_limit: reports one member may file per rolling 24 hours.
    | details_max: longest free-text description of a report.
    */
    'reports' => [
        'daily_limit' => (int) env('LP_REPORT_DAILY_LIMIT', 5),
        'details_max' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Billing (plans, credits, payments)
    |--------------------------------------------------------------------------
    | currency: the only currency used. Money is stored as integer poisha.
    | free_unlocks: contact unlocks every member gets once, without paying.
    |   Set 0 to turn the free unlock off.
    | max_pending_checkouts_per_hour: how many unfinished payments one member may
    |   start per hour.
    | stale_minutes: an unfinished payment older than this is cancelled by a
    |   scheduled command (added in a later step).
    | reminder_days: how many days before a paid period ends the member is reminded.
    |   0 sends no reminder.
    */
    'billing' => [
        'currency' => 'BDT',
        'free_unlocks' => (int) env('LP_FREE_UNLOCKS', 1),
        'max_pending_checkouts_per_hour' => (int) env('LP_MAX_PENDING_CHECKOUTS', 5),
        'stale_minutes' => (int) env('LP_STALE_PAYMENT_MINUTES', 60),
        // A member is told this many days before a paid period ends. 0 sends no reminder.
        'reminder_days' => (int) env('LP_MEMBERSHIP_REMINDER_DAYS', 3),

        // Manual bKash. The number lives in .env (LP_BKASH_NUMBER), never in code.
        // While it is empty, checkout is switched off.
        'bkash_number' => (string) env('LP_BKASH_NUMBER', ''),
        'bkash_account_type' => 'personal',
        // Payments waiting for staff, per member. Stops a flood of unchecked claims.
        'max_under_review' => (int) env('LP_MAX_UNDER_REVIEW', 3),
        // Largest payment screenshot, in kilobytes.
        'proof_max_kb' => (int) env('LP_PROOF_MAX_KB', 5120),
    ],

    /*
    |--------------------------------------------------------------------------
    | Identity verification
    |--------------------------------------------------------------------------
    | max_per_day: how many requests one member may send in 24 hours.
    | expire_days: a request still waiting after this many days is cancelled and its files deleted.
    | max_kb: largest document picture, in kilobytes.
    */
    'identity' => [
        'max_per_day' => (int) env('LP_IDENTITY_MAX_PER_DAY', 3),
        'expire_days' => (int) env('LP_IDENTITY_EXPIRE_DAYS', 30),
        'max_kb' => (int) env('LP_IDENTITY_MAX_KB', 5120),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    | keep_read_days: a notification that was read is deleted after this many days.
    */
    'notifications' => [
        'keep_read_days' => (int) env('LP_NOTIFICATION_KEEP_READ_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS
    |--------------------------------------------------------------------------
    | driver: smsnetbd (real sending), log (writes to the log, not in production)
    |   or null (SMS switched off). Anything else is treated as null.
    | api_key: LP_SMS_API_KEY. It lives in .env only, never in code or logs.
    | base_url: the provider's API address (no trailing slash needed).
    | sender_id: an approved sender id from the provider, or empty.
    | timeout_seconds: how long to wait for the provider.
    | daily_cap_per_member: urgent SMS one member may get per rolling 24 hours.
    |   0 sends no SMS at all.
    | otp_daily_cap_global: verification codes the whole site may send per day.
    |   A brake against someone burning the balance on strangers' numbers.
    | keep_days: how long sms_messages rows (no numbers, no text) are kept.
    */
    'sms' => [
        'driver' => (string) env('LP_SMS_DRIVER', 'null'),
        'api_key' => (string) env('LP_SMS_API_KEY', ''),
        'base_url' => (string) env('LP_SMS_BASE_URL', 'https://api.sms.net.bd'),
        'sender_id' => (string) env('LP_SMS_SENDER_ID', ''),
        'timeout_seconds' => (int) env('LP_SMS_TIMEOUT', 8),
        'daily_cap_per_member' => (int) env('LP_SMS_DAILY_CAP', 5),
        'otp_daily_cap_global' => (int) env('LP_SMS_OTP_DAILY_CAP', 300),
        'keep_days' => (int) env('LP_SMS_KEEP_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | First admin (used only by AdminUserSeeder)
    |--------------------------------------------------------------------------
    | There is no default admin and no default password. To create the first admin, put these
    | three lines in .env, run the seeder by name (it is NOT part of db:seed), then delete
    | LP_ADMIN_PASSWORD from .env:
    |   LP_ADMIN_NAME, LP_ADMIN_EMAIL, LP_ADMIN_PASSWORD (at least 12 characters)
    */
    'admin' => [
        'name' => (string) env('LP_ADMIN_NAME', ''),
        'email' => (string) env('LP_ADMIN_EMAIL', ''),
        'password' => (string) env('LP_ADMIN_PASSWORD', ''),
    ],

];
