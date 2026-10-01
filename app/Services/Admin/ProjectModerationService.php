<?php

namespace App\Services\Admin;

use App\Models\ModelJob;
use App\Models\Staff;
use App\Notifications\ProjectRestoredNotification;
use App\Notifications\ProjectTakenDownNotification;
use App\Support\Staff\StaffAudit;

/** Taking a project off the board, and putting it back. The poster is told either way, with the reason. */
class ProjectModerationService
{
    public function takeDown(ModelJob $job, Staff $by, string $reason): ?string
    {
        if ($job->isTakenDown()) {
            return 'This project is already taken down.';
        }

        $job->forceFill(['taken_down_at' => now(), 'taken_down_reason' => trim($reason), 'taken_down_by' => $by->id, 'is_active' => false])->save();
        $job->loadMissing('user')->user->notify(new ProjectTakenDownNotification($job));
        StaffAudit::log('project.taken-down', "Took down \"{$job->title}\"", $job, ['reason' => trim($reason)], $by->id);

        return null;
    }

    public function restore(ModelJob $job, Staff $by): ?string
    {
        if (! $job->isTakenDown()) {
            return 'This project is not taken down.';
        }

        // It comes back open only if that is still sensible (not archived, deadline not passed); otherwise the poster reopens it
        $reopen = ! $job->is_archived && ($job->no_deadline || $job->deadline === null || ! $job->deadline->isBefore(today()));

        $job->forceFill(['taken_down_at' => null, 'taken_down_reason' => null, 'taken_down_by' => null, 'is_active' => $reopen])->save();
        $job->loadMissing('user')->user->notify(new ProjectRestoredNotification($job));
        StaffAudit::log('project.restored', "Restored \"{$job->title}\"", $job, staffId: $by->id);

        return null;
    }
}
