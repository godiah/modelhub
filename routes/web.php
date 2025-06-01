<?php

use App\Http\Controllers\Admin\AdminDisputeController;
use App\Http\Controllers\DashBoardController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\JobDeliverableController;
use App\Http\Controllers\JobEngagementController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MessageTemplateController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PartialPaymentController;
use App\Http\Controllers\PolicyManagementController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');
/**
 * User Dashboard
 */
Route::middleware(['auth'])->prefix('my-dashboard')->name('my-dashboard.')->group(function () {
    Route::get('/', [DashBoardController::class, 'index'])->name('index');
});

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
    Route::get('/applications/archived', [JobApplicationController::class, 'archived'])->name('archived');
    Route::post('/applications/{application}/archive', [JobApplicationController::class, 'archive'])->name('archive');
    Route::post('/applications/{application}/restore', [JobApplicationController::class, 'restore'])->name('restore');
    Route::delete('/applications/{application}', [JobApplicationController::class, 'destroy'])->name('destroy');
    Route::get('/submitted/{job:slug}', [JobApplicationController::class, 'show'])->name('show');
});

// My Applications
Route::middleware(['auth'])->group(function () {
    Route::get('/my-applications', [JobApplicationController::class, 'getUserApplications'])->name('applications.my');
    Route::get('/my-drafts', [JobApplicationController::class, 'getDraftApplications'])->name('applications.drafts');
    Route::delete('/my-drafts/{application}', [JobApplicationController::class, 'destroyDraft'])->name('destroy.drafts');
});

// My Posted Jobs and Applications Management
Route::middleware(['auth'])->prefix('my-jobs')->name('my-jobs.')->group(function () {
    Route::get('/', [JobApplicationController::class, 'getUserPostedJobs'])->name('index');

    Route::prefix('applications')->name('applications.')->group(function () {
        Route::get('/{slug}', [JobApplicationController::class, 'getJobApplications'])->name('index');
        Route::get('/{application}/details', [JobApplicationController::class, 'showApplications'])->name('show');
        Route::patch('/{application}/status', [JobApplicationController::class, 'updateStatus'])->name('update-status');
        Route::post('/{application}/message', [JobApplicationController::class, 'sendMessage'])->name('send-message');
        Route::post('/{application}/confirm-hire', [JobApplicationController::class, 'confirmHire'])->name('confirm-hire');
    });

    Route::prefix('archived')->name('archived.')->group(function () {
        Route::get('/', [JobApplicationController::class, 'archivedJobs'])->name('posted-jobs');
        Route::patch('/{job}/restore', [JobApplicationController::class, 'restoreJob'])->name('restore');
        Route::get('/{job}', [JobApplicationController::class, 'showArchivedJob'])->name('show');
    });

    Route::patch('/{job}/archive', [JobApplicationController::class, 'archiveJob'])->name('archive');
});

// Email Message Templates
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

// Job Engagements
Route::middleware(['auth'])->prefix('engagements')->name('engagements.')->group(function () {
    Route::get('/', [JobEngagementController::class, 'index'])->name('index');
    Route::get('/{id}', [JobEngagementController::class, 'showCancelledEngagement'])->name('show-cancelled');
    Route::get('/disputed/{id}', [JobEngagementController::class, 'showDisputedEngagement'])->name('show-disputed');
    Route::post('/{id}/process-payment', [PartialPaymentController::class, 'processPartialPayment'])->name('process-partial-payment');
    Route::post('/partial-payments/{id}/accept', [PartialPaymentController::class, 'acceptPartialPayment'])->name('accept-partial-payment');
    Route::get('/partial-payments/{id}/dispute', [PartialPaymentController::class, 'disputePartialPayment'])->name('dispute-form');
    Route::post('/partial-payments/{id}/process-dispute', [PartialPaymentController::class, 'processDisputePartialPayment'])->name('process-dispute-partial-payment');

    // Archive functionality
    Route::prefix('archive')->group(function () {
        Route::post('/', [JobEngagementController::class, 'archive'])->name('archive');
        Route::get('/archived', [JobEngagementController::class, 'archivedEngagements'])->name('archived');
        Route::post('/restore', [JobEngagementController::class, 'restore'])->name('restore');
        Route::get('/{engagement}/details', [JobEngagementController::class, 'show'])->name('archived-details');
    });

    // Accept & Reject Engagement
    Route::middleware(['verify-engagement-ownership'])->group(function () {
        Route::get('/{applicationId}/respond', [JobEngagementController::class, 'showResponseForm'])->name('response-form');
        Route::post('/{engagement}/respond', [JobEngagementController::class, 'respondToOffer'])->name('respond');
    });

    // Deliverable routes
    Route::prefix('deliverables')->name('deliverables.')->group(function () {
        Route::post('/{deliverable}/submit', [JobDeliverableController::class, 'submit'])->name('submit');
        Route::post('/{deliverable}/approve', [JobDeliverableController::class, 'approve'])->name('approve');
        Route::post('/{deliverable}/reject', [JobDeliverableController::class, 'reject'])->name('reject');
        Route::patch('/{deliverable}', [JobDeliverableController::class, 'update'])->name('update');
        Route::delete('/{deliverable}', [JobDeliverableController::class, 'destroy'])->name('destroy');
        Route::post('/{engagement}/store', [JobDeliverableController::class, 'store'])->name('store');
    });

    // Review routes
    Route::post('/{engagement}/review', [JobEngagementController::class, 'leaveReview'])->name('review');

    // Cancellation routes
    Route::get('/{engagement}/cancel', [JobEngagementController::class, 'showCancellationForm'])->name('cancel.form');
    Route::post('/{engagement}/cancel', [JobEngagementController::class, 'cancelEngagement'])->name('cancel');
    Route::post('/{engagement}/reopen-job', [JobEngagementController::class, 'reopenJob'])->name('reopen-job');

    // Policy routes
    Route::get('/policies/cancellation', [PolicyManagementController::class, 'index'])->name('policy');
});

// Projects Dashboard
Route::middleware(['auth'])->prefix('projects')->name('project.')->group(function () {
    Route::get('/', [ProjectController::class, 'index'])->name('index');
    Route::get('/engagement/{engagement}', [ProjectController::class, 'show'])->name('engagement.show');
    Route::post('/engagement/{engagement}/archive', [ProjectController::class, 'archive'])->name('engagement.archive');
    Route::post('/engagement/{engagement}/unarchive', [ProjectController::class, 'unarchive'])->name('engagement.unarchive');
});

// Client - Freelancer Messaging
Route::middleware(['auth'])->prefix('chat')->group(function () {
    Route::get('/engagements/{engagement}/data', [MessageController::class, 'getEngagementData'])->name('engagements.data');
    Route::post('/engagements/{engagement}/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::patch('/engagements/{engagement}/messages/read', [MessageController::class, 'markAsRead'])->name('messages.read');
    //Route::get('/messages/unread-count', [MessageController::class, 'unreadCount'])->name('messages.unread-count');
});

/**
 * Administrator Routes
 */
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/disputes', [AdminDisputeController::class, 'index'])->name('disputes.index');
    Route::post('/disputes/{id}/assign', [AdminDisputeController::class, 'assign'])->name('disputes.assign');
    Route::post('/disputes/{dispute}/resolve', [AdminDisputeController::class, 'resolve'])->name('disputes.resolve');
});




require __DIR__ . '/auth.php';
