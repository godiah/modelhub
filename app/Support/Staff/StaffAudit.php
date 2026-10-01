<?php

namespace App\Support\Staff;

use App\Models\StaffActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * The staff activity log. Call StaffAudit::log() when staff do something that matters: a moderation decision, a change
 * to staff or roles, a sign-in. The actor is whoever is signed in to the staff portal, unless one is passed.
 */
final class StaffAudit
{
    public static function log(string $action, string $summary, ?Model $subject = null, array $details = [], ?int $staffId = null): StaffActivity
    {
        return StaffActivity::create([
            'staff_id' => $staffId ?? Auth::guard(StaffAccess::GUARD)->id(),
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'summary' => mb_substr($summary, 0, 255),
            'details' => $details ?: null,
            'ip_address' => request()?->ip(),
        ]);
    }
}
