<?php

use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\MessageTemplateController;
use App\Http\Controllers\NotificationController;
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
    Route::get('/', [JobController::class, 'index'])->name('index');
    Route::get('/browse', [JobController::class, 'browseJobs'])->name('browse');
    Route::get('/create', [JobController::class, 'new'])->name('create');
    Route::post('/', [JobController::class, 'store'])->name('store');
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

// Message Templates
Route::get('/my-jobs/message-templates', [MessageTemplateController::class, 'index'])
    ->middleware(['auth'])->name('my-jobs.message-templates');
Route::post('/my-jobs/message-templates', [MessageTemplateController::class, 'store'])
    ->middleware(['auth'])->name('my-jobs.message-templates.store');
Route::delete('/my-jobs/message-templates/{template}', [MessageTemplateController::class, 'destroy'])
    ->middleware(['auth'])->name('my-jobs.message-templates.destroy');

// Notifications
Route::middleware(['auth'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::get('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
    Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
    Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('delete');
    Route::delete('/', [NotificationController::class, 'destroyAll'])->name('delete-all');
});






require __DIR__ . '/auth.php';
