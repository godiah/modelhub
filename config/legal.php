<?php

/*
|--------------------------------------------------------------------------
| Legal details used by the Terms, Privacy and policy pages
|--------------------------------------------------------------------------
|
| The wording of those pages lives in resources/views/legal. Only the facts that belong to the business
| are configured here, so they can be corrected without touching the text. They are placeholders until
| the owner confirms them: set the LEGAL_* values in the environment, and have the documents reviewed
| by a lawyer before relying on them.
|
*/

return [
    // Who "we" are in the documents
    'entity' => env('LEGAL_ENTITY_NAME', env('APP_NAME', 'ModelHub')),

    // A postal address, shown only when set
    'address' => env('LEGAL_ADDRESS'),

    // Where legal and privacy questions go
    'email' => env('LEGAL_EMAIL', env('MAIL_SUPPORT_ADDRESS', env('MAIL_FROM_ADDRESS', 'hello@example.com'))),

    // Whose law governs the terms and which courts hear disputes
    'jurisdiction' => env('LEGAL_JURISDICTION', 'Kenya'),

    // When the payments and earnings guide (resources/policies/payments.md) was last changed in substance (Y-m-d): change it with the text
    'payments_policy_updated' => env('LEGAL_PAYMENTS_POLICY_UPDATED', '2026-10-02'),

    // When the current Terms and Privacy Policy took effect (Y-m-d)
    'effective' => env('LEGAL_EFFECTIVE_DATE', '2026-10-01'),

    // Minimum age to hold an account
    'minimum_age' => 18,
];
