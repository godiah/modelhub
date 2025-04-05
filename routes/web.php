<?php

use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\JobController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// JobBoard
// Job Home Page
Route::get('/jobs', [JobController::class, 'index'])->name('job.home');
// Show the new job form
Route::get('/jobs/new', [JobController::class, 'new'])->name('job.new');
// List all available jobs
Route::get('/jobs/browse', [JobController::class, 'browseJobs'])->name('jobs.browse');
// Show a single job
Route::get('/jobs/{job:slug}', [JobController::class, 'show'])->name('job.show');
// Save a new job
Route::post('/jobs/create', [JobController::class, 'store'])->name('job.store');
// Check if a job title already exists
Route::get('/check-title', [JobController::class, 'checkTitle']);
// Apply for a job: show application form
Route::get('/jobs/browse/{job:slug}/apply', [JobController::class, 'apply'])
    ->name('jobs.apply');

Route::middleware(['auth'])->group(function () {
    // Store a new application (draft or submitted)
    Route::post('/applications', [JobApplicationController::class, 'store'])->name('applications.store');

    // Submit a draft application
    Route::get('/applications/continue/{id}', [JobApplicationController::class, 'continueDraft'])->name('applications.continue');

    // Get all user's applications
    Route::get('/my-applications', [JobApplicationController::class, 'getUserApplications'])->name('my.applications');

    // Get user's draft applications
    Route::get('/my-drafts', [JobApplicationController::class, 'getDraftApplications'])->name('my.drafts');

    // Delete an application
    Route::delete('/applications/{id}', [JobApplicationController::class, 'destroy'])->name('applications.destroy');

    // View Submitted Application Details
    Route::get('/submitted/applications/{job:slug}', [JobApplicationController::class, 'show'])->name('applications.show');
});

/** Posted Jobs Routes */
Route::middleware(['auth'])->prefix('my-jobs')->group(function () {
    // View jobs posted by the user
    Route::get('/', [JobApplicationController::class, 'getUserPostedJobs'])->name('my.posted.jobs');

    // View applications for a posted job
    Route::get('/{id}/applications', [JobApplicationController::class, 'getJobApplications'])->name('jobs.applications');

    // View application details
    Route::get('/applications/{application}/details', [JobApplicationController::class, 'showApplications'])->name('show-applications');

    // Update application status
    Route::patch('/applications/{application}/status', [JobApplicationController::class, 'updateStatus'])->name('applications.update-status');

    // Send message to applicant
    Route::post('/applications/{application}/message', [JobApplicationController::class, 'sendMessage'])->name('applications.send-message');

    // Edit and update a job
    Route::get('/jobs/{job:slug}/edit', [JobController::class, 'edit'])->name('jobs.edit');
    Route::patch('/jobs/{job:slug}', [JobController::class, 'update'])->name('jobs.update');
});



require __DIR__ . '/auth.php';
