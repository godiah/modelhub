<?php

// JobImageService manages image-related operations for job postings, including main and additional image uploads.
// This service handles the storage of images in the public disk and associates additional images with the job model,
// ensuring proper file handling and database integration for job-related imagery.

namespace App\Services\Jobs;

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
}
