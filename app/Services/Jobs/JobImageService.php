<?php

// JobImageService manages image-related operations for job postings, including main and additional image uploads.
// This service handles the storage of images in the public disk and associates additional images with the job model,
// ensuring proper file handling and database integration for job-related imagery.

namespace App\Services\Jobs;

use App\Models\ModelJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        if (! $request->hasFile('additional_images')) {
            return;
        }

        $additionalImages = $request->file('additional_images');

        foreach ($additionalImages as $index => $additionalImage) {
            $imagePath = $additionalImage->store('job_additional_images', 'public');

            $job->jobImages()->create([
                'model_job_id' => $job->id,
                'image_path' => $imagePath,
            ]);
        }
    }

    // Replace the cover image, deleting the old file
    public function replaceMainImage(Request $request, ModelJob $job): void
    {
        if (! $request->hasFile('image')) {
            return;
        }

        $old = $job->images;
        $job->update(['images' => $request->file('image')->store('job_images', 'public')]);

        if ($old) {
            Storage::disk('public')->delete($old);
        }
    }

    // Remove some of the project's extra images (only ones that belong to it) and their files
    public function removeAdditionalImages(ModelJob $job, array $imageIds): void
    {
        if ($imageIds === []) {
            return;
        }

        $job->jobImages()->whereIn('id', $imageIds)->get()->each(function ($image) {
            Storage::disk('public')->delete($image->image_path);
            $image->delete();
        });
    }
}
