<?php

// Handle application CRUD, status updates, archiving

namespace App\Services\Applications;

use App\Enums\ApplicationStatus;
use App\Helpers\Applications\ApplicationCalculationHelper;
use App\Helpers\Applications\ApplicationFileHelper;
use App\Helpers\FlashAlertHelper;
use App\Models\ApplicantMessage;
use App\Models\JobApplication;
use App\Models\ModelJob;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class ApplicationManagementService
{
    // Store a new application or update existing draft
    public function store(Request $request, array $processedData): JobApplication
    {
        // Calculate service fee and net amount
        $amounts = ApplicationCalculationHelper::calculateAmounts($processedData['offer_amount']);

        // Handle portfolio files
        $existingApplication = $this->getExistingApplication($processedData['job_id'], $processedData['applicant_id']);
        $existingFiles = $existingApplication && ! empty($existingApplication->portfolio) ? $existingApplication->portfolio : [];

        $portfolioFiles = ApplicationFileHelper::handlePortfolioFiles($request, $existingFiles);

        // Prepare application data
        $applicationData = array_merge($amounts, [
            'proposal' => $processedData['proposal'],
            'portfolio' => $portfolioFiles,
            'status' => $processedData['status'],
        ]);

        // Handle terms acceptance
        if (isset($processedData['terms_accepted'])) {
            $applicationData['terms_accepted'] = $processedData['terms_accepted'];
        }

        // Update existing draft or create new application
        if ($existingApplication && $existingApplication->status === ApplicationStatus::Draft) {
            $existingApplication->update($applicationData);

            return $existingApplication;
        } else {
            // Create new application
            $applicationData = array_merge($applicationData, [
                'job_id' => $processedData['job_id'],
                'applicant_id' => $processedData['applicant_id'],
                'poster_id' => $processedData['poster_id'],
            ]);

            return JobApplication::create($applicationData);
        }
    }

    // Validate application constraints
    public function validateApplicationConstraints(array $processedData): ?array
    {
        // Check if the job exists and is active
        $job = ModelJob::findOrFail($processedData['job_id']);
        if (! $job->isOpenForApplications()) {
            return ['error' => 'This job is no longer accepting new applications'];
        }

        // Check if the user is authorized to apply
        if ($processedData['applicant_id'] != Auth::id()) {
            return ['error' => 'Unauthorized action'];
        }

        // Posters cannot apply to their own projects
        if ($job->user_id === $processedData['applicant_id']) {
            return FlashAlertHelper::error('Action Not Allowed', 'You cannot apply to your own project.');
        }

        // Check for existing applications
        $existingApplicationWithDeleted = JobApplication::withTrashed()
            ->where('job_id', $processedData['job_id'])
            ->where('applicant_id', $processedData['applicant_id'])
            ->first();

        $existingApplication = JobApplication::where('job_id', $processedData['job_id'])
            ->where('applicant_id', $processedData['applicant_id'])
            ->first();

        $isDraft = $processedData['status'] === 'draft';

        // Validate application constraints
        if ($existingApplication && $isDraft) {
            $prohibitedStatuses = [
                ApplicationStatus::Submitted,
                ApplicationStatus::Reviewed,
                ApplicationStatus::Rejected,
                ApplicationStatus::Hired,
                ApplicationStatus::Withdrawn,
            ];
            if (in_array($existingApplication->status, $prohibitedStatuses, true)) {
                return FlashAlertHelper::error(
                    'Action Not Allowed',
                    'Your application has already been '.$existingApplication->status->value.'. You cannot create a draft version of it.'
                );
            }
        }

        // Prevent reapplying after deletion
        if ($existingApplicationWithDeleted && $existingApplicationWithDeleted->deleted_at) {
            return FlashAlertHelper::error(
                'Reapplication Not Allowed',
                'You have previously deleted your application for this job and cannot apply again.'
            );
        }

        // One application per project: anything already sent (whatever its status) stands
        if (! $isDraft && $existingApplication && $existingApplication->status !== ApplicationStatus::Draft) {
            return FlashAlertHelper::info(
                'Your Application Already Exists',
                'You have already applied to this project. You can follow it under My applications.'
            );
        }

        return null; // No validation errors
    }

    // Load draft application for editing
    public function getDraftApplication(string $slug): array
    {
        $job = ModelJob::with('user.profile')->where('slug', $slug)->firstOrFail();

        $application = JobApplication::where('job_id', $job->id)
            ->where('applicant_id', Auth::id())
            ->where('status', 'draft')
            ->firstOrFail();

        return compact('application', 'job');
    }

    // Delete a draft. It was never sent, so it is removed for good (a soft delete would block applying to the
    // project again) together with the files that were attached to it.
    public function deleteDraft(JobApplication $application): bool
    {
        if ($application->applicant_id !== Auth::id() || $application->status !== ApplicationStatus::Draft) {
            return false;
        }

        ApplicationFileHelper::deleteApplicationPortfolio($application->portfolio ?? []);
        $application->forceDelete();

        return true;
    }

    // The applicant's sent applications (everything but drafts), newest first unless sorted otherwise
    public function getUserApplications(array $filters): LengthAwarePaginator
    {
        $query = $this->sentApplications()
            ->with('job', 'engagement', 'jobEngagements')
            ->active();

        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        $this->applySearch($query, $filters['search'] ?? null);
        $this->applySortingToApplications($query, $filters['sort']);

        return $query->paginate(8)->withQueryString();
    }

    /** Applications per status for the filter pills, plus how many are archived and how many drafts exist. */
    public function getApplicationCounts(): array
    {
        $byStatus = $this->sentApplications()->active()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'all' => (int) $byStatus->sum(),
            'byStatus' => $byStatus->map(fn ($total) => (int) $total)->all(),
            'archived' => $this->sentApplications()->archived()->count(),
            'drafts' => $this->getDraftCount(),
        ];
    }

    // Get draft applications count
    public function getDraftCount(): int
    {
        return JobApplication::draft()
            ->where('applicant_id', Auth::id())
            ->count();
    }

    // Get user's draft applications
    public function getDraftApplications(): LengthAwarePaginator
    {
        return JobApplication::with('job')
            ->where('applicant_id', Auth::id())
            ->where('status', 'draft')
            ->latest('updated_at')
            ->paginate(9);
    }

    // Get application details for viewing (the signed-in user's own application to this job)
    public function getApplicationDetails(ModelJob $job): JobApplication
    {
        return JobApplication::with(['job', 'poster.profile', 'engagement', 'jobEngagements'])
            ->where('job_id', $job->id)
            ->where('applicant_id', Auth::id())
            ->firstOrFail();
    }

    // Messages the client sent about an application, newest first
    public function getClientMessages(JobApplication $application)
    {
        return ApplicantMessage::with('sender')
            ->where('job_application_id', $application->id)
            ->where('recipient_id', Auth::id())
            ->latest()
            ->limit(10)
            ->get();
    }

    // Archive an application
    public function archiveApplication(JobApplication $application): bool
    {
        if (! $application->canBeArchived()) {
            return false;
        }

        $application->update(['is_archived' => true]);

        return true;
    }

    // Restore an archived application
    public function restoreApplication(JobApplication $application): bool
    {
        if (! $application->is_archived) {
            return false;
        }

        $application->update(['is_archived' => false]);

        return true;
    }

    // Delete an archived application (soft delete). One tied to an engagement stays: the engagement needs it.
    public function deleteArchivedApplication(JobApplication $application): bool
    {
        if (! $application->is_archived || $application->hasEngagement()) {
            return false;
        }

        $application->delete();

        return true;
    }

    // Get archived applications
    public function getArchivedApplications(): LengthAwarePaginator
    {
        return $this->sentApplications()
            ->with('job', 'engagement', 'jobEngagements')
            ->archived()
            ->latest('updated_at')
            ->paginate(9);
    }

    protected function sentApplications()
    {
        return JobApplication::where('applicant_id', Auth::id())->where('status', '!=', ApplicationStatus::Draft->value);
    }

    protected function applySearch($query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term !== '') {
            $query->whereHas('job', fn ($job) => $job->where('title', 'like', '%'.addcslashes($term, '%_\\').'%'));
        }
    }

    // Apply sorting to applications query
    protected function applySortingToApplications($query, string $sort): void
    {
        switch ($sort) {
            case 'date_asc':
                $query->orderBy('created_at', 'asc');
                break;
            case 'status':
                $query->orderBy('status', 'asc')->latest();
                break;
            case 'offer_high':
                $query->orderByDesc('offer_amount');
                break;
            case 'offer_low':
                $query->orderBy('offer_amount');
                break;
            case 'date_desc':
            default:
                $query->latest();
        }
    }

    // Get existing application
    protected function getExistingApplication(int $jobId, int $applicantId): ?JobApplication
    {
        return JobApplication::where('job_id', $jobId)
            ->where('applicant_id', $applicantId)
            ->first();
    }
}
