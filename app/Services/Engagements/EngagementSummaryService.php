<?php

namespace App\Services\Engagements;

use App\Enums\EngagementStatus;
use App\Models\JobEngagement;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Everything an engagement row needs at a glance, computed once per page from eager-loaded data:
 * the user's role, the counterpart, honest progress (approved deliverables only), the next deadline,
 * what is waiting on the user, and unread messages. Requires application.job/poster/applicant and
 * deliverables to be loaded (lazy loading is blocked outside production).
 */
class EngagementSummaryService
{
    /**
     * @param  Collection<int, JobEngagement>  $engagements
     * @return array<int, array<string, mixed>> keyed by engagement id
     */
    public function summaries(Collection $engagements, User $user): array
    {
        $unread = $this->unreadMessages($engagements, $user);
        $summaries = [];

        foreach ($engagements as $engagement) {
            $summaries[$engagement->id] = $this->summarise($engagement, $user, $unread[$engagement->id] ?? 0);
        }

        return $summaries;
    }

    /**
     * @return array<string, mixed>
     */
    private function summarise(JobEngagement $engagement, User $user, int $unreadMessages): array
    {
        $application = $engagement->application;
        $isApplicant = $application->applicant_id === $user->id;
        $deliverables = $engagement->deliverables;

        $total = $deliverables->count();
        $approved = $deliverables->where('status', 'approved')->count();

        $open = $deliverables->whereIn('status', ['pending', 'rejected'])->whereNotNull('due_date');
        $nextDue = $open->min('due_date');
        $nextDue = $nextDue ? Carbon::parse($nextDue) : null;
        $overdue = $nextDue !== null && $nextDue->isPast() && ! $nextDue->isToday();

        $active = $engagement->status === EngagementStatus::Active;

        return [
            'role' => $isApplicant ? 'freelancer' : 'client',
            'counterpart' => ($isApplicant ? $application->poster : $application->applicant)?->name,
            'total' => $total,
            'approved' => $approved,
            'percent' => $total > 0 ? (int) round($approved / $total * 100) : 0,
            'next_due' => $active ? $nextDue : null,
            'overdue' => $active && $overdue,
            // Waiting on this user: the client reviews submitted work, the freelancer revises rejected work.
            'to_review' => $active && ! $isApplicant ? $deliverables->where('status', 'submitted')->count() : 0,
            'to_revise' => $active && $isApplicant ? $deliverables->where('status', 'rejected')->count() : 0,
            'unread_messages' => $unreadMessages,
            'amount' => $isApplicant ? (float) $engagement->net_amount : (float) $engagement->agreed_amount,
            'amount_label' => $isApplicant ? 'You receive' : 'You pay',
        ];
    }

    /**
     * Unread messages from the other party, per engagement, in one grouped query.
     *
     * @param  Collection<int, JobEngagement>  $engagements
     * @return array<int, int>
     */
    private function unreadMessages(Collection $engagements, User $user): array
    {
        if ($engagements->isEmpty()) {
            return [];
        }

        return Message::whereNull('read_at')
            ->where('sender_id', '!=', $user->id)
            ->whereIn('engagement_id', $engagements->modelKeys())
            ->select('engagement_id', DB::raw('count(*) as unread'))
            ->groupBy('engagement_id')
            ->pluck('unread', 'engagement_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }
}
