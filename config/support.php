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

];
