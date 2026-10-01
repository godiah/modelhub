<?php

namespace App\Services\Admin;

use App\Models\MemberNote;
use App\Models\Staff;
use App\Models\User;
use App\Notifications\AccountReinstatedNotification;
use App\Notifications\AccountSuspendedNotification;
use App\Notifications\TwoFactorResetNotification;
use App\Support\Staff\StaffAudit;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * What staff can do to a member account: suspend and reinstate it, keep private notes, and help with passwords and
 * verification (without ever seeing or setting a password). Every action is recorded in the activity log.
 */
class MemberManagementService
{
    /** Returns why it was refused, or null once done. */
    public function suspend(User $member, Staff $by, string $reason): ?string
    {
        if ($member->isSuspended()) {
            return 'This account is already suspended.';
        }

        $member->forceFill(['suspended_at' => now(), 'suspended_reason' => trim($reason), 'suspended_by' => $by->id, 'remember_token' => Str::random(60)])->save();
        $member->notify(new AccountSuspendedNotification(trim($reason)));
        StaffAudit::log('member.suspended', "Suspended {$member->name}", $member, ['reason' => trim($reason)], $by->id);

        return null;
    }

    public function reinstate(User $member, Staff $by): ?string
    {
        if (! $member->isSuspended()) {
            return 'This account is not suspended.';
        }

        $member->forceFill(['suspended_at' => null, 'suspended_reason' => null, 'suspended_by' => null])->save();
        $member->notify(new AccountReinstatedNotification);
        StaffAudit::log('member.reinstated', "Reinstated {$member->name}", $member, staffId: $by->id);

        return null;
    }

    public function addNote(User $member, Staff $by, string $body): MemberNote
    {
        $note = $member->notes()->create(['staff_id' => $by->id, 'body' => trim($body)]);
        StaffAudit::log('member.note-added', "Added a note on {$member->name}", $member, staffId: $by->id);

        return $note;
    }

    public function sendPasswordReset(User $member, Staff $by): void
    {
        Password::broker('users')->sendResetLink(['email' => $member->email]);
        StaffAudit::log('member.password-reset-sent', "Sent a password reset link to {$member->name}", $member, staffId: $by->id);
    }

    /** For a member who lost their phone and recovery codes: removes their authenticator app and tells them by email. */
    public function resetTwoFactor(User $member, Staff $by): void
    {
        $member->resetTwoFactor();
        $member->notify(new TwoFactorResetNotification(route('profile').'#security'));
        StaffAudit::log('member.two-factor-reset', "Reset two-step sign-in for {$member->name}", $member, staffId: $by->id);
    }

    public function resendVerification(User $member, Staff $by): ?string
    {
        if ($member->hasVerifiedEmail()) {
            return 'This member has already verified their email address.';
        }

        $member->sendEmailVerificationNotification();
        StaffAudit::log('member.verification-sent', "Resent the verification email to {$member->name}", $member, staffId: $by->id);

        return null;
    }
}
