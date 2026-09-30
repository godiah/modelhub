<?php

// Manages portfolio file operations for job applications, including uploading, deleting, and updating files.

namespace App\Helpers\Applications;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApplicationFileHelper
{
    // Handle portfolio file operations for applications. The form can only keep or remove files the application
    // already owns: paths it names that are not in $existingFiles are ignored, never trusted.
    public static function handlePortfolioFiles(Request $request, array $existingFiles = []): array
    {
        $portfolioFiles = $existingFiles;

        if ($request->has('existing_portfolio')) {
            $portfolioFiles = array_values(array_intersect((array) $request->input('existing_portfolio', []), $existingFiles));
        }

        $removedFiles = array_values(array_intersect((array) $request->input('removed_files', []), $existingFiles));
        if ($removedFiles !== []) {
            self::deleteFiles($removedFiles);
            $portfolioFiles = array_diff($portfolioFiles, $removedFiles);
        }

        if ($request->hasFile('portfolio')) {
            $portfolioFiles = array_merge($portfolioFiles, self::uploadNewFiles($request->file('portfolio')));
        }

        return array_values($portfolioFiles);
    }

    // Upload new portfolio files
    protected static function uploadNewFiles(array $files): array
    {
        $uploadedFiles = [];

        // Log::info('Number of files: ' . count($files));

        foreach ($files as $index => $file) {
            // Log::info("Processing file {$index}: " . $file->getClientOriginalName() . " - Type: " . $file->getMimeType());

            // Create a unique filename using index and microtime to avoid collisions
            $uniquePrefix = microtime(true).'_'.$index.'_';
            $fileName = $uniquePrefix.$file->getClientOriginalName();
            $filePath = $file->storeAs('portfolios', $fileName, 'public');
            $uploadedFiles[] = $filePath;
        }

        return $uploadedFiles;
    }

    // Delete files from storage
    public static function deleteFiles(array $filePaths): void
    {
        foreach ($filePaths as $filePath) {
            Storage::disk('public')->delete($filePath);
        }
    }

    // Delete portfolio files from an application
    public static function deleteApplicationPortfolio(array $portfolio): void
    {
        if (! empty($portfolio) && is_array($portfolio)) {
            self::deleteFiles($portfolio);
        }
    }
}
