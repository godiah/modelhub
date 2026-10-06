<?php

namespace App\Http\Middleware\Support;

use App\Models\User;
use App\Services\Support\Inbound\ReadClaimVerifier;
use App\Services\Support\Inbound\SupportRequestRejected;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Third check: who is this about. The member comes only from the signed read claim (never from the URL or the query), must be active and in the
 * current stage, and is rate-limited by id, since every call from the agent shares one address.
 */
class VerifySupportMember
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $member = ReadClaimVerifier::fromConfig()->member($request->header('X-Support-Read-Claim'));
            $this->ensureInStage($member);
            $this->ensureWithinLimit($member);
        } catch (SupportRequestRejected $e) {
            Log::warning('Support read refused', ['reason' => $e->reason, 'principal' => $request->attributes->get('support.principal')]);

            $response = $e->toResponse();
            if ($e->status === 429) {
                $response->headers->set('Retry-After', '60');
            }

            return $response;
        }

        $request->attributes->set('support.member', $member);

        return $next($request);
    }

    private function ensureInStage(User $member): void
    {
        $allowed = match (config('support.reads.stage')) {
            'all' => true,
            'pilot' => in_array($member->getKey(), (array) config('support.reads.pilot_member_ids'), true),
            default => false, // an unknown stage is closed, not open
        };

        if (! $allowed) {
            throw new SupportRequestRejected('member_not_in_stage', 403);
        }
    }

    private function ensureWithinLimit(User $member): void
    {
        $key = 'support-reads:member:'.$member->getKey();
        $limit = (int) config('support.reads.throttle_per_minute');

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            throw new SupportRequestRejected('throttled', 429);
        }

        RateLimiter::hit($key, 60);
    }
}
