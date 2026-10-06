<?php

namespace App\Services\Support\Inbound;

use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Which addresses may call the read API. Matched against the connecting address, never a forwarded header: this app has no trusted-proxy
 * setup, and a header the caller writes is not evidence of where it came from. The signature is the main control; this is a second layer.
 */
final class CallerAllowList
{
    private const LOCAL_RANGES = ['127.0.0.0/8', '::1', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'];

    public static function allows(string $ip): bool
    {
        $configured = (array) config('support.reads.allowed_ips');

        if ($configured !== []) {
            return IpUtils::checkIp($ip, $configured);
        }

        // Nothing configured: deny, except on a developer's machine, where the agent is on loopback or a Docker network
        return app()->environment('local') && IpUtils::checkIp($ip, self::LOCAL_RANGES);
    }
}
