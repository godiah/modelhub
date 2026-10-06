<?php

namespace App\Services\Support\Inbound;

use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Remembers each request nonce so a captured request cannot be sent twice. If the store cannot be reached the call is refused (503),
 * not let through: replay protection is not something to lose quietly.
 */
class RequestNonces
{
    /** True if the nonce was new. */
    public function claim(string $key, int $ttlSeconds): bool
    {
        try {
            return Cache::store(config('support.reads.nonce_store'))->add('support-reads:nonce:'.$key, 1, $ttlSeconds);
        } catch (Throwable $e) {
            throw new SupportRequestRejected('nonce_store_unavailable', 503, $e);
        }
    }
}
