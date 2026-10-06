<?php

namespace App\Services\Support;

use App\Models\User;

/** Whether the assistant may read this member's own records right now: reads switched on, and the member inside the current stage. */
final class SupportReads
{
    public static function inStage(User $member): bool
    {
        return match (config('support.reads.stage')) {
            'all' => true,
            'pilot' => in_array($member->getKey(), (array) config('support.reads.pilot_member_ids'), true),
            default => false, // an unknown stage is closed, not open
        };
    }

    /** Used by the panel to decide whether to offer "Check my latest withdrawal". The read API enforces the same rules itself. */
    public static function withdrawalsAvailableTo(?User $member): bool
    {
        return $member !== null
            && config('support.enabled')
            && config('support.reads.enabled')
            && config('support.reads.capabilities.withdrawals')
            && self::inStage($member);
    }
}
