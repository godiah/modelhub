<?php

// JobManagementService handles CRUD operations for job postings, including creation, updates, and retrieval.
// This service integrates image handling, slug generation, and user authorization to manage job data,
// ensuring database consistency, proper notifications, and access control for job-related actions.

namespace App\Services\Jobs;

use App\Models\ModelJob;
use App\Models\Skill;
use App\Models\Software;
use App\Notifications\JobPostedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JobManagementService
{
    protected JobImageService $imageService;
    protected JobSlugService $slugService;

    public function __construct(JobImageService $imageService, JobSlugService $slugService)
    {
        $this->imageService = $imageService;
        $this->slugService = $slugService;
    }

    // Get data for creating a new job (skills & software)
    public function getNewJobData(): array
    {
        return [
            'skills' => Skill::where('is_active', true)->get(),
            'software' => Software::where('is_active', true)->get()
        ];
    }

    // Store a new job
    public function store(Request $request, array $validatedData): ModelJob
    {
        // Handle main image upload
        $imagePath = $this->imageService->handleMainImage($request);
        if ($imagePath) {
            $validatedData['image'] = $imagePath;
        }

        // Generate unique slug
        $slug = $this->slugService->generateUniqueSlug($validatedData['title']);

        $job = DB::transaction(function () use ($request, $validatedData, $slug) {
            // Create the job associated with the authenticated user
            $job = Auth::user()->jobs()->create([
                'title'       => $validatedData['title'],
                'slug'        => $slug,
                'description' => $validatedData['description'] ?? null,
                'skills'      => $validatedData['skills'],
                'software'    => $validatedData['software'],
                'images'      => $validatedData['image'] ?? null,
                'deadline'    => $validatedData['deadline'],
                'no_deadline' => $validatedData['no_deadline'],
                'budget'      => $validatedData['budget'],
            ]);

            // Handle additional images
            $this->imageService->handleAdditionalImages($request, $job);

            // Send notification
            Auth::user()->notify(new JobPostedNotification($job));

            return $job;
        });

        return $job;
    }

    // Get data for editing a job
    public function getEditJobData(ModelJob $job): array
    {
        return [
            'job' => $job,
            'skills' => Skill::where('is_active', true)->get(),
            'software' => Software::where('is_active', true)->get()
        ];
    }

    // Update a job
    public function update(ModelJob $job, array $updateData): ModelJob
    {
        $job->update($updateData);
        return $job;
    }

    // Check if user can view the job (authorization check) --- Move to a policy
    public function authorizeJobView(ModelJob $job): bool
    {
        return $job->user_id === Auth::id();
    }

    // Check if a job title exists
    public function titleExists(string $title): bool
    {
        return ModelJob::where('title', $title)->exists();
    }
}
