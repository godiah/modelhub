<?php

// Handle application CRUD, status updates, archiving

namespace App\Services\Applications;

use App\Enums\ApplicationStatus;
use App\Helpers\Applications\ApplicationCalculationHelper;
use App\Helpers\Applications\ApplicationFileHelper;
use App\Helpers\FlashAlertHelper;
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
        if (! $job->is_active || ! $job->isActive()) {
            return ['error' => 'This job is no longer accepting new applications'];
        }

        // Check if the user is authorized to apply
        if ($processedData['applicant_id'] != Auth::id()) {
            return ['error' => 'Unauthorized action'];
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

        // Prevent multiple drafts
        if ($isDraft && $existingApplication && $existingApplication->status === ApplicationStatus::Draft) {
            return FlashAlertHelper::info(
                'Existing Draft',
                'You already have a draft application for this job. Please edit the existing draft or submit it.'
            );
        }

        // Prevent reapplying after deletion
        if ($existingApplicationWithDeleted && $existingApplicationWithDeleted->deleted_at) {
            return FlashAlertHelper::error(
                'Reapplication Not Allowed',
                'You have previously deleted your application for this job and cannot apply again.'
            );
        }

        // Check for existing submitted application
        if (! $isDraft && $existingApplication && $existingApplication->status === ApplicationStatus::Submitted) {
            return FlashAlertHelper::info(
                'Your Application Already Exists',
                'You have already submitted an application for this job. Your previous application is still pending review.'
            );
        }

        return null; // No validation errors
    }

    // Load draft application for editing
    public function getDraftApplication(string $slug): array
    {
        $job = ModelJob::where('slug', $slug)->firstOrFail();

        $application = JobApplication::where('job_id', $job->id)
            ->where('applicant_id', Auth::id())
            ->where('status', 'draft')
            ->firstOrFail();

        return compact('application', 'job');
    }

    // Delete an application or draft
    public function deleteApplication(JobApplication $application): bool
    {
        // Authorization check
        if ($application->applicant_id !== Auth::id()) {
            return false;
        }

        // Remove portfolio files
        ApplicationFileHelper::deleteApplicationPortfolio($application->portfolio ?? []);

        // Delete the application
        $application->delete();

        return true;
    }

    // Get user's applications with filtering
    public function getUserApplications(array $filters): LengthAwarePaginator
    {
        $query = JobApplication::with('job', 'engagement', 'jobEngagements')
            ->where('applicant_id', Auth::id())
            ->where('status', '!=', 'draft')
            ->active();

        // Apply status filter
        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        // Apply sorting
        $this->applySortingToApplications($query, $filters['sort']);

        return $query->paginate(7)->withQueryString();
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
            ->latest()
            ->paginate(10);
    }

    // Get application details for viewing
    public function getApplicationDetails(ModelJob $job): JobApplication
    {
        return JobApplication::with(['job', 'applicant', 'poster'])
            ->where('job_id', $job->id)
            ->where('applicant_id', Auth::user()->id)
            ->firstOrFail();
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
        $application->update(['is_archived' => false]);

        return true;
    }

    // Delete an archived application (soft delete)
    public function deleteArchivedApplication(JobApplication $application): bool
    {
        if (! $application->is_archived) {
            return false;
        }

        $application->delete();

        return true;
    }

    // Get archived applications
    public function getArchivedApplications(): LengthAwarePaginator
    {
        return JobApplication::with(['job', 'applicant', 'poster'])
            ->where('applicant_id', Auth::id())
            ->archived()
            ->latest()
            ->paginate(10);
    }

    // Apply sorting to applications query
    protected function applySortingToApplications($query, string $sort): void
    {
        switch ($sort) {
            case 'date_asc':
                $query->orderBy('created_at', 'asc');
                break;
            case 'status':
                $query->orderBy('status', 'asc');
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
