<?php

// JobSlugService generates and validates unique slugs for job titles.
// This service ensures that each job has a unique, URL-friendly slug by appending numeric suffixes
// when necessary, preventing conflicts in the database and supporting clean routing.

namespace App\Services\Jobs;

use App\Models\ModelJob;
use Illuminate\Support\Str;

class JobSlugService
{
    // Generate a unique slug for a job title
    public function generateUniqueSlug(string $title): string
    {
        // Convert to slug (replace spaces with dashes, convert to lowercase)
        $slug = Str::slug($title);

        // Check if the slug already exists
        $originalSlug = $slug;
        $count = 1;

        while (ModelJob::where('slug', $slug)->exists()) {
            // If slug exists, append a number
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }
}
