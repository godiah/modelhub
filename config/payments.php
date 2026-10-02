<?php

/*
|--------------------------------------------------------------------------
| Taking payments
|--------------------------------------------------------------------------
|
| Money comes in through one gateway, chosen here. "fake" simulates M-Pesa so the whole checkout can be built and tested without
| Safaricom credentials: it must never run in production. "daraja" is real M-Pesa (STK Push to collect, B2C to pay out) through the godiah/laravel-common package, configured by its MPESA_* settings.
|
*/

return [
    'gateway' => env('PAYMENTS_GATEWAY', 'fake'),

    // Money going out (withdrawals) uses its own gateway: "fake", or "daraja" for M-Pesa B2C (needs B2C enabled on the shortcode by Safaricom)
    'payout_gateway' => env('PAYMENTS_PAYOUT_GATEWAY', 'fake'),

    // The fake gateway refuses to run in production unless this is set (it should never be)
    'allow_fake_in_production' => (bool) env('PAYMENTS_ALLOW_FAKE_IN_PRODUCTION', false),

    // An M-Pesa prompt that has not been answered after this long is given up on
    'pending_minutes' => (int) env('PAYMENTS_PENDING_MINUTES', 5),

    // M-Pesa takes whole shillings, between these amounts (KES) for a single payment
    'min_kes' => 1,
    'max_kes' => (int) env('PAYMENTS_MAX_KES', 150000),

    // Daraja signs nothing it sends us, so each callback URL carries this secret (16+ random characters) and requests without it are refused
    'daraja' => [
        'callback_secret' => env('PAYMENTS_DARAJA_CALLBACK_SECRET'),
    ],

    // A withdrawal still "being sent" this long after approval has not been confirmed by M-Pesa: staff are told, and settle it by hand
    'payout_stale_hours' => (int) env('PAYMENTS_PAYOUT_STALE_HOURS', 6),

    // The fake gateway: how long a simulated payment stays pending before it resolves
    'fake' => [
        'delay_seconds' => (int) env('PAYMENTS_FAKE_DELAY', 3),
    ],
];
