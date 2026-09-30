<?php

namespace App\Services\Dashboard;

use App\Enums\ApplicationStatus;
use App\Enums\EngagementStatus;
use App\Helpers\NotificationPresenterHelper;
use App\Models\JobApplication;
use App\Models\JobEngagement;
use App\Models\JobReview;
use App\Models\Message;
use App\Models\ModelJob;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds the signed-in dashboard: an action-focused home (what needs attention, what is in flight)
 * rather than a profile page. Everything is eager-loaded — lazy loading is blocked outside production.
 */
class DashboardOverviewService
{
    /** Pending deliverables due within this many days are surfaced as needing attention. */
    private const DUE_SOON_DAYS = 3;

    /** Rows shown in the attention queue before "+N more". */
    private const ATTENTION_LIMIT = 8;

    /**
     * @return array<string, mixed>
     */
    public function overview(User $user): array
    {
        $engagements = $this->openEngagements($user);
        $attention = $this->attentionItems($user, $engagements);

        return [
            'summary' => $this->summary($user, $engagements, count($attention)),
            'attention' => array_slice($attention, 0, self::ATTENTION_LIMIT),
            'attentionMore' => max(0, count($attention) - self::ATTENTION_LIMIT),
            'engagements' => $this->activeEngagementCards($user, $engagements),
            'applications' => $this->recentApplications($user),
            'projects' => $this->postedProjects($user),
            'notifications' => $this->recentNotifications($user),
            'profile' => $this->profileCard($user),
            'reviews' => $this->recentReviews($user),
        ];
    }

    /**
     * Engagements the user is a party to that can still need something from someone
     * (pending offer, active work, open dispute), with everything the dashboard reads eager-loaded.
     *
     * @return Collection<int, JobEngagement>
     */
    private function openEngagements(User $user): Collection
    {
        return JobEngagement::with(['application.job', 'application.applicant', 'application.poster', 'deliverables'])
            ->activeForUser($user->id)
            ->whereIn('status', [EngagementStatus::EmployerAccepted, EngagementStatus::Active, EngagementStatus::Disputed])
            ->latest('started_at')
            ->get();
    }

    /**
     * @param  Collection<int, JobEngagement>  $engagements
     * @return array{active: int, needs_action: int, earned: float, open_applications: int, posted_projects: int}
     */
    private function summary(User $user, Collection $engagements, int $needsAction): array
    {
        return [
            'active' => $engagements->where('status', EngagementStatus::Active)->count(),
            'needs_action' => $needsAction,
            'earned' => (float) JobEngagement::where('status', EngagementStatus::Completed)
                ->forApplicant($user->id)
                ->sum('net_amount'),
            'open_applications' => JobApplication::where('applicant_id', $user->id)
                ->whereIn('status', [ApplicationStatus::Submitted, ApplicationStatus::Reviewed])
                ->where('is_archived', false)
                ->count(),
            'posted_projects' => ModelJob::where('user_id', $user->id)->unarchived()->count(),
        ];
    }

    /**
     * Everything waiting on this user, most urgent first.
     *
     * @param  Collection<int, JobEngagement>  $engagements
     * @return list<array{icon: string, tone: string, title: string, detail: string, url: string, cta: string, priority: int}>
     */
    private function attentionItems(User $user, Collection $engagements): array
    {
        $items = [];
        $soon = now()->addDays(self::DUE_SOON_DAYS)->endOfDay();

        foreach ($engagements as $engagement) {
            $application = $engagement->application;
            $jobTitle = $application->job->title;
            $isApplicant = $application->applicant_id === $user->id;
            $engagementUrl = route('engagements.archived-details', $engagement->id);

            if ($engagement->status === EngagementStatus::EmployerAccepted && $isApplicant) {
                $items[] = $this->item('paper-airplane', 'amber', __('Respond to offer'), $jobTitle,
                    route('engagements.response-form', ['applicationId' => $application->id]), __('Respond'), 10);
            }

            if ($engagement->status === EngagementStatus::Disputed) {
                $items[] = $this->item('shield-check', 'red', __('Dispute in progress'), $jobTitle,
                    route('engagements.show-disputed', $engagement->id), __('View'), 40);
            }

            if ($engagement->status !== EngagementStatus::Active) {
                continue;
            }

            foreach ($engagement->deliverables as $deliverable) {
                if ($deliverable->status === 'submitted' && ! $isApplicant) {
                    $items[] = $this->item('document-text', 'amber', __('Review deliverable: :title', ['title' => $deliverable->title]),
                        $jobTitle, $engagementUrl, __('Review'), 20);
                } elseif ($deliverable->status === 'rejected' && $isApplicant) {
                    $items[] = $this->item('pencil-square', 'red', __('Revise deliverable: :title', ['title' => $deliverable->title]),
                        $jobTitle, $engagementUrl, __('Open'), 20);
                } elseif ($deliverable->status === 'pending' && $isApplicant && $deliverable->due_date && $deliverable->due_date->lte($soon)) {
                    $overdue = $deliverable->due_date->isPast() && ! $deliverable->due_date->isToday();
                    $items[] = $this->item('clock', $overdue ? 'red' : 'amber',
                        $overdue
                            ? __('Overdue: :title', ['title' => $deliverable->title])
                            : __('Due soon: :title', ['title' => $deliverable->title]),
                        $jobTitle.' · '.__('due :date', ['date' => $deliverable->due_date->format('M j')]),
                        $engagementUrl, __('Open'), $overdue ? 15 : 30);
                }
            }
        }

        $items = [...$items, ...$this->unreadMessageItems($user, $engagements), ...$this->newApplicantItems($user)];

        usort($items, fn (array $a, array $b) => $a['priority'] <=> $b['priority']);

        return $items;
    }

    /**
     * @param  Collection<int, JobEngagement>  $engagements
     * @return list<array<string, mixed>>
     */
    private function unreadMessageItems(User $user, Collection $engagements): array
    {
        if ($engagements->isEmpty()) {
            return [];
        }

        $unread = Message::whereNull('read_at')
            ->where('sender_id', '!=', $user->id)
            ->whereIn('engagement_id', $engagements->modelKeys())
            ->select('engagement_id', DB::raw('count(*) as unread'))
            ->groupBy('engagement_id')
            ->pluck('unread', 'engagement_id');

        return $engagements->filter(fn (JobEngagement $e) => $unread->has($e->id))
            ->map(fn (JobEngagement $e) => $this->item('chat-bubble-left-right', 'blue',
                trans_choice(':count unread message|:count unread messages', (int) $unread[$e->id], ['count' => (int) $unread[$e->id]]),
                $e->application->job->title, route('engagements.archived-details', $e->id), __('Reply'), 35))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function newApplicantItems(User $user): array
    {
        return JobApplication::with('job')
            ->where('poster_id', $user->id)
            ->where('status', ApplicationStatus::Submitted)
            ->where('is_archived', false)
            ->whereHas('job', fn ($query) => $query->unarchived())
            ->get()
            ->groupBy('job_id')
            ->map(fn (Collection $applications) => $this->item('users', 'blue',
                trans_choice(':count new application|:count new applications', $applications->count(), ['count' => $applications->count()]),
                $applications->first()->job->title,
                route('my-jobs.applications.index', $applications->first()->job->slug), __('Review'), 50))
            ->values()
            ->all();
    }

    /**
     * @return array{icon: string, tone: string, title: string, detail: string, url: string, cta: string, priority: int}
     */
    private function item(string $icon, string $tone, string $title, string $detail, string $url, string $cta, int $priority): array
    {
        return compact('icon', 'tone', 'title', 'detail', 'url', 'cta', 'priority');
    }

    /**
     * Cards for work in flight: role, counterpart, deliverable progress and the next deadline.
     *
     * @param  Collection<int, JobEngagement>  $engagements
     * @return list<array<string, mixed>>
     */
    private function activeEngagementCards(User $user, Collection $engagements): array
    {
        return $engagements->where('status', EngagementStatus::Active)
            ->take(4)
            ->map(function (JobEngagement $engagement) use ($user) {
                $application = $engagement->application;
                $isApplicant = $application->applicant_id === $user->id;
                $total = $engagement->deliverables->count();
                $approved = $engagement->deliverables->where('status', 'approved')->count();
                $nextDue = $engagement->deliverables->where('status', '!=', 'approved')->whereNotNull('due_date')->min('due_date');

                return [
                    'url' => route('engagements.archived-details', $engagement->id),
                    'title' => $application->job->title,
                    'role' => $isApplicant ? __('Freelancer') : __('Client'),
                    'counterpart' => ($isApplicant ? $application->poster : $application->applicant)?->name,
                    'approved' => $approved,
                    'total' => $total,
                    'percent' => $total > 0 ? (int) round($approved / $total * 100) : 0,
                    'next_due' => $nextDue ? Carbon::parse($nextDue) : null,
                    'overdue' => $nextDue && Carbon::parse($nextDue)->isPast() && ! Carbon::parse($nextDue)->isToday(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, JobApplication>
     */
    private function recentApplications(User $user): Collection
    {
        return JobApplication::with('job')
            ->where('applicant_id', $user->id)
            ->where('status', '!=', ApplicationStatus::Draft)
            ->where('is_archived', false)
            ->latest()
            ->limit(3)
            ->get();
    }

    /**
     * @return Collection<int, ModelJob>
     */
    private function postedProjects(User $user): Collection
    {
        return ModelJob::where('user_id', $user->id)->unarchived()->latest()->limit(3)->get();
    }

    /**
     * @return list<array{id: string, content: string, read: bool, at: Carbon}>
     */
    private function recentNotifications(User $user): array
    {
        return $user->notifications()->limit(5)->get()
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'content' => NotificationPresenterHelper::present($notification)['content'],
                'read' => $notification->read_at !== null,
                'at' => $notification->created_at,
            ])
            ->all();
    }

    /**
     * Identity, reputation and how complete the profile is.
     *
     * @return array<string, mixed>
     */
    private function profileCard(User $user): array
    {
        $user->loadMissing(['profile', 'skills', 'software']);
        $profile = $user->profile;

        $checks = [
            __('Add a profile photo') => (bool) ($profile?->avatar),
            __('Describe your professional background') => (bool) ($profile?->professional_info),
            __('Add your location') => (bool) ($profile?->location),
            __('Add a phone number') => (bool) ($profile?->telephone_number),
            __('Add your skills') => $user->skills->isNotEmpty(),
            __('Add the software you use') => $user->software->isNotEmpty(),
        ];

        $stats = JobReview::where('reviewee_id', $user->id)
            ->where('is_public', true)
            ->selectRaw('COUNT(*) as total, AVG(rating) as average')
            ->first();

        return [
            'location' => $profile?->location,
            'skills' => $user->skills->take(5),
            'rating' => $stats->average ? round($stats->average, 1) : null,
            'review_count' => (int) ($stats->total ?? 0),
            'completeness' => (int) round(count(array_filter($checks)) / count($checks) * 100),
            'next_step' => array_search(false, $checks, true) ?: null,
        ];
    }

    /**
     * @return Collection<int, JobReview>
     */
    private function recentReviews(User $user): Collection
    {
        return JobReview::with([
            'reviewer' => fn ($query) => $query->select('id', 'name'),
            'reviewer.profile' => fn ($query) => $query->select('user_id', 'avatar'),
            'engagement' => fn ($query) => $query->select('id', 'application_id', 'created_at'),
            'engagement.job' => fn ($query) => $query->select('model_jobs.id', 'model_jobs.title'),
        ])
            ->where('reviewee_id', $user->id)
            ->where('is_public', true)
            ->latest()
            ->limit(3)
            ->get();
    }
}
