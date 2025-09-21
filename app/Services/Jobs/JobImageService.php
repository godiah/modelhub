<?php

// JobImageService manages image-related operations for job postings, including main and additional image uploads.
// This service handles the storage of images in the public disk and associates additional images with the job model,
// ensuring proper file handling and database integration for job-related imagery.
// Service also handles application portfolio images for applicants of a job.

namespace App\Services\Jobs;

use App\Helpers\Applications\ApplicationFileHelper;
use Illuminate\Http\Request;

class JobImageService
{    
    // Handle main image upload
    public function handleMainImage(Request $request): ?string
    {
        if ($request->hasFile('image')) {
            return $request->file('image')->store('job_images', 'public');
        }

        return null;
    }

    // Handle additional images upload
    public function handleAdditionalImages(Request $request, $job): void
    {
        if (!$request->hasFile('additional_images')) {
            return;
        }

        $additionalImages = $request->file('additional_images');

        foreach ($additionalImages as $index => $additionalImage) {
            $imagePath = $additionalImage->store('job_additional_images', 'public');

            $job->jobImages()->create([
                'model_job_id' => $job->id,
                'image_path' => $imagePath
            ]);
        }
    }

    // Handle portfolio files for applications
    public function handlePortfolioFiles(Request $request, array $existingFiles = []): array
    {
        return ApplicationFileHelper::handlePortfolioFiles($request, $existingFiles);
    }

    // Delete portfolio files for applications
    public function deletePortfolioFiles(array $portfolio): void
    {
        ApplicationFileHelper::deleteApplicationPortfolio($portfolio);
    }
}
