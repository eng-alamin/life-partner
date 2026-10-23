<?php

/*
|--------------------------------------------------------------------------
| bKash payment API (Tokenized Checkout)
|--------------------------------------------------------------------------
| Everything secret lives in .env, never in code:
|   LP_BKASH_API_ENABLED   true or false. While false, "pay with bKash" is switched off.
|   LP_BKASH_API_SANDBOX   true (default) uses the bKash test system. Set false only for real money.
|   LP_BKASH_APP_KEY, LP_BKASH_APP_SECRET, LP_BKASH_USERNAME, LP_BKASH_PASSWORD
|                          the four values bKash gives a merchant account.
|   LP_BKASH_API_BASE_URL  optional. Normally empty: the address is chosen from the sandbox switch.
|   LP_BKASH_API_TIMEOUT   seconds to wait for bKash (default 20).
|
| The addresses below follow the public documentation of Tokenized Checkout v1.2.0-beta.
| Check them against the official developer.bka.sh pages when you receive your credentials.
*/

$sandbox = filter_var(env('LP_BKASH_API_SANDBOX', true), FILTER_VALIDATE_BOOLEAN);

return [
    'enabled' => filter_var(env('LP_BKASH_API_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'sandbox' => $sandbox,

    'app_key' => (string) env('LP_BKASH_APP_KEY', ''),
    'app_secret' => (string) env('LP_BKASH_APP_SECRET', ''),
    'username' => (string) env('LP_BKASH_USERNAME', ''),
    'password' => (string) env('LP_BKASH_PASSWORD', ''),

    'base_url' => (string) (env('LP_BKASH_API_BASE_URL')
        ?: ($sandbox
            ? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized'
            : 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized')),

    'timeout' => (int) env('LP_BKASH_API_TIMEOUT', 20),
];
