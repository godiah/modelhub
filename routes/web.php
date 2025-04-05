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

// Job Public Routes (No auth)
Route::prefix('jobs')->name('jobs.')->group(function () {
    Route::get('/', [JobController::class, 'index'])->name('index'); // job.home
    Route::get('/browse', [JobController::class, 'browseJobs'])->name('browse');
    Route::get('/create', [JobController::class, 'new'])->name('create'); // job.new
    Route::post('/', [JobController::class, 'store'])->name('store'); // job.store
    Route::get('/{job:slug}', [JobController::class, 'show'])->name('show');
    Route::get('/{job:slug}/apply', [JobController::class, 'apply'])->name('apply');

    Route::get('/{job:slug}/edit', [JobController::class, 'edit'])->middleware('auth')->name('edit');
    Route::patch('/{job:slug}', [JobController::class, 'update'])->middleware('auth')->name('update');
});

// Check if a job title already exists
Route::get('/check-title', [JobController::class, 'checkTitle'])->name('jobs.check-title');

// Application Routes (User Applications)
Route::middleware(['auth'])->prefix('applications')->name('applications.')->group(function () {
    Route::post('/', [JobApplicationController::class, 'store'])->name('store');
    Route::get('/continue/{slug}', [JobApplicationController::class, 'continueDraft'])->name('continue');
    Route::delete('/{id}', [JobApplicationController::class, 'destroy'])->name('destroy');
    Route::get('/submitted/{job:slug}', [JobApplicationController::class, 'show'])->name('show');
});

// My Applications
Route::middleware(['auth'])->group(function () {
    Route::get('/my-applications', [JobApplicationController::class, 'getUserApplications'])->name('applications.my');
    Route::get('/my-drafts', [JobApplicationController::class, 'getDraftApplications'])->name('applications.drafts');
});

// My Posted Jobs and Applications Management
Route::middleware(['auth'])->prefix('my-jobs')->name('my-jobs.')->group(function () {
    Route::get('/', [JobApplicationController::class, 'getUserPostedJobs'])->name('index');


    Route::prefix('applications')->name('applications.')->group(function () {
        Route::get('/{slug}', [JobApplicationController::class, 'getJobApplications'])->name('index');
        Route::get('/{application}/details', [JobApplicationController::class, 'showApplications'])->name('show');
        Route::patch('/{application}/status', [JobApplicationController::class, 'updateStatus'])->name('update-status');
        Route::post('/{application}/message', [JobApplicationController::class, 'sendMessage'])->name('send-message');
    });
});





require __DIR__ . '/auth.php';
