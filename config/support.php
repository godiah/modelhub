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

];
