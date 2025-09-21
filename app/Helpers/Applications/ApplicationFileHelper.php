<?php

// Manages portfolio file operations for job applications, including uploading, deleting, and updating files.

namespace App\Helpers\Applications;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApplicationFileHelper
{
    // Handle portfolio file operations for applications
    public static function handlePortfolioFiles(Request $request, array $existingFiles = []): array
    {
        // Start with existing portfolio files if any
        $portfolioFiles = [];

        // Add any existing files that weren't removed
        if ($request->has('existing_portfolio')) {
            $portfolioFiles = $request->input('existing_portfolio', []);
        } elseif (!empty($existingFiles)) {
            $portfolioFiles = $existingFiles;
        }

        // Remove any files that were marked for removal
        if ($request->has('removed_files')) {
            $removedFiles = $request->input('removed_files', []);

            // Remove the files from storage
            self::deleteFiles($removedFiles);

            // Remove the files from our list
            $portfolioFiles = array_diff($portfolioFiles, $removedFiles);
        }

        // Process new portfolio files if any
        if ($request->hasFile('portfolio')) {
            $newFiles = self::uploadNewFiles($request->file('portfolio'));
            $portfolioFiles = array_merge($portfolioFiles, $newFiles);
        }

        // Reindex the array (in case there were removals)
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
            $uniquePrefix = microtime(true) . '_' . $index . '_';
            $fileName = $uniquePrefix . $file->getClientOriginalName();
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
        if (!empty($portfolio) && is_array($portfolio)) {
            self::deleteFiles($portfolio);
        }
    }
}
