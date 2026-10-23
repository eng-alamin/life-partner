<?php

return [
    'errors' => [
        'unavailable' => 'We could not open the bKash payment page. Please try again in a few minutes.',
    ],

    'plans' => [
        'pay_with_bkash' => 'Pay with bKash',
        'pay_manually' => 'Pay by Send Money instead',
    ],

    'result' => [
        'paid' => 'Your payment is confirmed. Thank you.',
        'failed' => 'The bKash payment did not go through. No money was taken. You can try again.',
        'cancelled' => 'The bKash payment was cancelled. No money was taken.',
        'pending' => 'We have not received a clear answer from bKash yet. If money left your account, it will be confirmed here shortly. Please do not pay again.',
        'attention' => 'We could not confirm this payment automatically. If money left your account, please contact us and keep your bKash message.',
        'ignored' => 'This payment page could not be used. You can see your payments below.',
    ],
];
