<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Support assistant: UI preview
    |--------------------------------------------------------------------------
    |
    | The support assistant is being designed visual-first: the chat widget renders scripted conversations from
    | App\Support\SupportChat\PreviewScenarios, with no backend behind it. This flag mounts the widget on every
    | signed-in member page so it can be judged in context. Off by default; turn it on locally only.
    |
    */

    'ui_preview' => (bool) env('SUPPORT_UI_PREVIEW', false),

    /*
    |--------------------------------------------------------------------------
    | Support assistant: the agent service
    |--------------------------------------------------------------------------
    |
    | The assistant runs as a separate service (the modelhub-support repo). ModelHub is the only thing that talks to it:
    | the chat panel posts to /support/chat here, and this app signs the request and vouches for who is asking.
    |
    | enabled  The kill switch. Off by default. Checked on every request, before anything leaves this app.
    | agent    Where the service is, and the two secrets that prove the request came from us:
    |          - hmac_*: signs the request (method, path, time, one-time nonce, body hash)
    |          - context_private_key: signs a 60-second "this is member N" claim (Ed25519). The service only holds the
    |            public half, so it can check a claim but never mint one. Generate both with `php artisan support:generate-keys`.
    |
    */

    'enabled' => (bool) env('SUPPORT_ENABLED', false),

    'agent' => [
        'url' => env('SUPPORT_AGENT_URL', 'http://localhost:8095'),
        'timeout' => (int) env('SUPPORT_AGENT_TIMEOUT', 120),
        'hmac_key_id' => env('SUPPORT_HMAC_KEY_ID', 'current'),
        'hmac_secret' => env('SUPPORT_HMAC_SECRET'),
        'context_private_key' => env('SUPPORT_CONTEXT_PRIVATE_KEY'), // base64 of a libsodium Ed25519 secret key
        'context_issuer' => 'modelhub',
        'context_audience' => 'support-agent',
        'context_ttl' => 60,
        // The claim that lets a staff member read ONE member's assistant chat for a ticket (T6): its own audience and scope
        'staff_claim_audience' => 'support-staff',
        'staff_claim_scope' => 'support:transcript:read',
    ],

    /*
    |--------------------------------------------------------------------------
    | Support assistant: read-only lookups of the member's own records (Phase 2)
    |--------------------------------------------------------------------------
    |
    | The other direction: the agent calls /api/support/v1/* here to read a member's own withdrawals, payments, licences and balance.
    | Every call must pass, in order: the switch, the caller's address, the request signature (its own secret, not the one above),
    | a read claim naming the member, the member being active and in the current stage, and a per-member rate limit. Failing any of
    | them answers the same way, so a caller learns nothing about which check it failed.
    |
    | enabled          The kill switch for reads. Off by default; separate from `enabled` above so chat can run without reads.
    | stage            pilot = only pilot_member_ids (staff's own test members); all = every active member. Anything else denies.
    | allowed_ips      The addresses (or CIDR blocks) the agent calls from, matched against the connecting address only. Never
    |                  X-Forwarded-For. Empty denies everything, except in `local`, where loopback and private ranges are allowed.
    | hmac_keys        key id => secret, two valid at once during rotation.
    | nonce_store      The cache store that remembers used nonces. Pin a shared one (redis) in production. If it is down, calls are
    |                  refused: replay protection is never silently dropped.
    |
    */

    'reads' => [
        'enabled' => (bool) env('SUPPORT_READS_ENABLED', false),
        'stage' => env('SUPPORT_READS_STAGE', 'pilot'),
        'pilot_member_ids' => array_values(array_filter(array_map('intval', explode(',', (string) env('SUPPORT_READS_PILOT_MEMBER_IDS', ''))))),
        'allowed_ips' => array_values(array_filter(array_map('trim', explode(',', (string) env('SUPPORT_READS_ALLOWED_IPS', ''))))),
        'hmac_keys' => array_filter([
            (string) env('SUPPORT_READS_HMAC_KEY_ID', 'current') => env('SUPPORT_READS_HMAC_SECRET'),
            (string) env('SUPPORT_READS_HMAC_PREVIOUS_KEY_ID', 'previous') => env('SUPPORT_READS_HMAC_PREVIOUS_SECRET'),
        ]),
        'max_skew' => 60,
        // An empty value (as in .env.example) means "the default cache store", not a store with no name
        'nonce_store' => env('SUPPORT_READS_NONCE_STORE') ?: null,
        'throttle_per_minute' => (int) env('SUPPORT_READS_THROTTLE_PER_MINUTE', 60),
        // Looking a payment up by M-Pesa code is the one read worth guessing at, so it has its own, lower limit per member
        'code_lookups_per_minute' => (int) env('SUPPORT_READS_CODE_LOOKUPS_PER_MINUTE', 10),
        // One switch per kind of read, each on by default once reads are on, so a single read can be turned off on its own
        'capabilities' => [
            'withdrawals' => (bool) env('SUPPORT_READS_WITHDRAWALS', true),
            'payments' => (bool) env('SUPPORT_READS_PAYMENTS', true),
            'balance' => (bool) env('SUPPORT_READS_BALANCE', true),
            'licences' => (bool) env('SUPPORT_READS_LICENCES', true),
        ],
        'claim_audience' => 'support-reads',
        'claim_scope' => 'support:read:self',
    ],

    /*
    |--------------------------------------------------------------------------
    | Support tickets: what members are told, and the clocks staff work to
    |--------------------------------------------------------------------------
    |
    | PROVISIONAL (O-11): the hours and the targets below are placeholders from modelhub-support/docs/09 until the owner decides the real ones. They are
    | kept here, in one place, so changing a number never means changing code. Times are minutes of BUSINESS time, counted in the business hours.
    | Members are shown them as "we aim to reply within...", never as a promise.
    |
    */

    'tickets' => [
        'timezone' => 'Africa/Nairobi',
        'business_days' => [1, 2, 3, 4, 5], // Monday to Friday (ISO)
        'business_hours' => ['start' => '08:00', 'end' => '18:00'],
        // severity => [first reply, resolution], in business minutes; null resolution = best effort
        'targets' => [
            'urgent' => ['first_response' => 60, 'resolution' => 600],
            'high' => ['first_response' => 240, 'resolution' => 600],
            'normal' => ['first_response' => 600, 'resolution' => 1800],
            'low' => ['first_response' => 1200, 'resolution' => null],
        ],
        // A member reply on a resolved ticket reopens it for this long; after that they start a new one
        'reopen_days' => 14,
        // Per member
        'max_open' => 5,
        'max_per_day' => 5,
        'max_replies_per_hour' => 20,
        // Files members and staff attach to a message. Images are JPEG or PNG only (this PHP build cannot re-encode WebP or HEIC), and every image is decoded
        // and written out again, which drops metadata (location, device) and anything hidden after the picture. PDFs are checked, never opened or previewed.
        'attachments' => [
            'disk' => env('SUPPORT_ATTACHMENTS_DISK', 'local'), // private: nothing here is ever served by the web server
            'max_files' => 5, // per message
            'max_bytes' => 5 * 1024 * 1024, // per file
            'max_per_ticket' => 25,
            'max_per_member_per_day' => 30,
            'max_pixels' => 25_000_000, // a picture claiming to be bigger is refused before it is decoded (a decompression bomb)
        ],
        // How long a ticket keeps the assistant's evidence snapshot after it is resolved (PROVISIONAL, part of O-04). The thread is kept; only the snapshot goes.
        'evidence_retention_days' => (int) env('SUPPORT_TICKET_EVIDENCE_DAYS', 90),
        // Words that make a ticket high severity whatever the records say
        'urgent_words' => ['scam', 'scammed', 'fraud', 'police', 'lawyer', 'court', 'stolen', 'hacked'],
    ],

];
