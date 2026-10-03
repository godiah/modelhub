<?php

use App\Http\Controllers\Auth\AuthenticatorController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashBoardController;
use App\Http\Controllers\EarningsController;
use App\Http\Controllers\EngagementFundingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\JobDeliverableController;
use App\Http\Controllers\JobEngagementController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\LicenceController;
use App\Http\Controllers\LicenceDownloadController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MessageTemplateController;
use App\Http\Controllers\ModelCatalogueController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PartialPaymentController;
use App\Http\Controllers\PaymentCallbackController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentsPolicyController;
use App\Http\Controllers\PayoutCallbackController;
use App\Http\Controllers\PayoutTimeoutController;
use App\Http\Controllers\PolicyManagementController;
use App\Http\Controllers\PostedJobApplicationController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\SellerProductController;
use App\Http\Controllers\SellerProductFileController;
use App\Http\Controllers\SellerStoreController;
use App\Http\Controllers\SellerStorefrontController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

// Signed-in users go straight to the app shell; the landing page is for guests.
Route::get('/', HomeController::class)->middleware('guest')->name('home');

Route::get('dashboard', [DashBoardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::patch('profile/avatar', [AvatarController::class, 'update'])->middleware('auth')->name('profile.avatar.update');

Route::middleware('auth')->prefix('profile/authenticator')->name('authenticator.')->group(function () {
    Route::post('/', [AuthenticatorController::class, 'start'])->middleware('throttle:10,1')->name('start');
    Route::post('confirm', [AuthenticatorController::class, 'confirm'])->middleware('throttle:10,1')->name('confirm');
    Route::delete('setup', [AuthenticatorController::class, 'cancel'])->name('cancel');
    Route::post('recovery-codes', [AuthenticatorController::class, 'recoveryCodes'])->middleware('throttle:10,1')->name('recovery');
    Route::delete('/', [AuthenticatorController::class, 'destroy'])->middleware('throttle:10,1')->name('destroy');
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Job Routes — static paths (create/browse) must stay registered before the
// {job:slug} wildcard below, or "create"/"browse" would themselves get
// matched as a slug and 404. Browsing/viewing is public; posting/editing
// requires auth.
Route::prefix('jobs')->name('jobs.')->group(function () {
    Route::get('/', [JobController::class, 'index'])->name('index');
    Route::get('/browse', [JobController::class, 'browseJobs'])->name('browse');
    Route::get('/create', [JobController::class, 'new'])->middleware('auth')->name('create');
    Route::post('/', [JobController::class, 'store'])->middleware('auth')->name('store');
    Route::post('/preview', [JobController::class, 'previewDescription'])->middleware(['auth', 'throttle:60,1'])->name('preview');

    Route::get('/{job:slug}', [JobController::class, 'show'])->name('show');
    Route::get('/{job:slug}/apply', [JobController::class, 'apply'])->name('apply');
    Route::get('/{job:slug}/edit', [JobController::class, 'edit'])->middleware('auth')->name('edit');
    Route::patch('/{job:slug}', [JobController::class, 'update'])->middleware('auth')->name('update');
});

// Check if a job title already exists
Route::get('/check-title', [JobController::class, 'checkTitle'])->middleware('auth')->name('jobs.check-title');

// Application Routes (User Applications)
Route::middleware(['auth'])->prefix('applications')->name('applications.')->group(function () {
    Route::middleware(['throttle:10,1'])->group(function () {
        Route::post('/', [JobApplicationController::class, 'store'])->name('store');
    });
    Route::get('/continue/{slug}', [JobApplicationController::class, 'continueDraft'])->name('continue');
    Route::get('/applications/archived', [JobApplicationController::class, 'archived'])->name('archived');
    Route::post('/applications/{application}/withdraw', [JobApplicationController::class, 'withdraw'])->name('withdraw');
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
    Route::get('/', [PostedJobApplicationController::class, 'getUserPostedJobs'])->name('index');

    Route::prefix('applications')->name('applications.')->group(function () {
        Route::get('/{slug}', [PostedJobApplicationController::class, 'getJobApplications'])->name('index');
        Route::get('/{application}/details', [PostedJobApplicationController::class, 'showApplications'])->name('show');
        Route::patch('/{application}/status', [PostedJobApplicationController::class, 'updateStatus'])->name('update-status');
        Route::post('/{application}/message', [PostedJobApplicationController::class, 'sendMessage'])->name('send-message');
        Route::post('/{application}/confirm-hire', [PostedJobApplicationController::class, 'confirmHire'])->name('confirm-hire');
    });

    Route::prefix('archived')->name('archived.')->group(function () {
        Route::get('/', [PostedJobApplicationController::class, 'archivedJobs'])->name('posted-jobs');
        Route::patch('/{job}/restore', [PostedJobApplicationController::class, 'restoreJob'])->name('restore');
        Route::get('/{job}', [PostedJobApplicationController::class, 'showArchivedJob'])->name('show');
    });

    Route::patch('/{job}/archive', [PostedJobApplicationController::class, 'archiveJob'])->name('archive');
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
    // The engagement workspace (deliverables, messages, activity). Numeric only, so it never shadows the
    // static segments below (archive, deliverables, policies, disputed, ...).
    Route::get('/{engagement}', [JobEngagementController::class, 'show'])->whereNumber('engagement')->name('show');
    Route::post('/{engagement}/fund', [EngagementFundingController::class, 'store'])->whereNumber('engagement')->middleware('throttle:10,1')->name('fund');
    Route::get('/{id}/cancelled', [JobEngagementController::class, 'showCancelledEngagement'])->whereNumber('id')->name('show-cancelled');
    Route::get('/disputed/{id}', [JobEngagementController::class, 'showDisputedEngagement'])->name('show-disputed');
    Route::middleware(['throttle:10,1'])->group(function () {
        Route::post('/{id}/process-payment', [PartialPaymentController::class, 'processPartialPayment'])->name('process-partial-payment');
        Route::post('/partial-payments/{id}/accept', [PartialPaymentController::class, 'acceptPartialPayment'])->name('accept-partial-payment');
        Route::post('/partial-payments/{id}/process-dispute', [PartialPaymentController::class, 'processDisputePartialPayment'])->name('process-dispute-partial-payment');
    });
    Route::get('/partial-payments/{id}/dispute', [PartialPaymentController::class, 'disputePartialPayment'])->name('dispute-form');
    Route::get('/disputes/{dispute}/evidence/{index}/download', [PartialPaymentController::class, 'downloadDisputeEvidence'])->name('disputes.download-evidence');

    // Archive functionality
    Route::prefix('archive')->group(function () {
        Route::post('/', [JobEngagementController::class, 'archive'])->name('archive');
        Route::get('/archived', [JobEngagementController::class, 'archivedEngagements'])->name('archived');
        Route::post('/restore', [JobEngagementController::class, 'restore'])->name('restore');
        // Legacy URL (stored in notifications and emails): forward to the workspace.
        Route::get('/{engagement}/details', [JobEngagementController::class, 'legacyDetails'])->name('archived-details');
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
        Route::get('/{deliverable}/files/{index}/download', [JobDeliverableController::class, 'downloadSubmissionFile'])->name('download-file');
    });

    // Review routes
    Route::post('/{engagement}/review', [JobEngagementController::class, 'leaveReview'])->name('review');

    // Cancellation routes
    Route::get('/{engagement}/cancel', [JobEngagementController::class, 'showCancellationForm'])->name('cancel.form');
    Route::post('/{engagement}/cancel', [JobEngagementController::class, 'cancelEngagement'])->name('cancel');
    Route::post('/{engagement}/reopen-job', [JobEngagementController::class, 'reopenJob'])->name('reopen-job');
});

// The public models catalogue
Route::get('/models', [ModelCatalogueController::class, 'index'])->name('models.index');
Route::get('/models/{product}', [ModelCatalogueController::class, 'show'])->name('models.show');
Route::get('/sellers/{seller:slug}', [SellerStorefrontController::class, 'show'])->name('sellers.show');

// Saved models
Route::middleware(['auth'])->group(function () {
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/models/{product}/wishlist', [WishlistController::class, 'toggle'])->middleware('throttle:60,1')->name('models.wishlist.toggle');
});

// Ratings and reviews of models
Route::middleware(['auth'])->group(function () {
    Route::post('/models/{product}/reviews', [ProductReviewController::class, 'store'])->middleware('throttle:20,1')->name('models.reviews.store');
    Route::patch('/reviews/{review}', [ProductReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/{review}', [ProductReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::post('/reviews/{review}/reply', [ProductReviewController::class, 'reply'])->middleware('seller')->name('reviews.reply');
    Route::delete('/reviews/{review}/reply', [ProductReviewController::class, 'destroyReply'])->middleware('seller')->name('reviews.reply.destroy');
    Route::post('/reviews/{review}/report', [ProductReviewController::class, 'report'])->middleware('throttle:20,1')->name('reviews.report');
});

// Selling 3D models: becoming an approved seller
Route::middleware(['auth'])->prefix('sell')->name('seller.')->group(function () {
    Route::get('/', [SellerController::class, 'index'])->name('index');
    Route::post('/apply', [SellerController::class, 'apply'])->middleware('throttle:6,1')->name('apply');
});

// An approved seller's store settings
Route::middleware(['auth', 'seller'])->prefix('sell/store')->name('seller.store.')->group(function () {
    Route::get('/', [SellerStoreController::class, 'edit'])->name('edit');
    Route::patch('/', [SellerStoreController::class, 'update'])->name('update');
});

// An approved seller's model listings
Route::middleware(['auth', 'seller'])->prefix('sell/models')->name('seller.models.')->group(function () {
    Route::get('/', [SellerProductController::class, 'index'])->name('index');
    Route::get('/create', [SellerProductController::class, 'create'])->name('create');
    Route::post('/', [SellerProductController::class, 'store'])->name('store');
    Route::get('/{product}/edit', [SellerProductController::class, 'edit'])->name('edit');
    Route::patch('/{product}', [SellerProductController::class, 'update'])->name('update');
    Route::delete('/{product}', [SellerProductController::class, 'destroy'])->name('destroy');
    Route::post('/{product}/submit', [SellerProductController::class, 'submit'])->name('submit');
    Route::post('/{product}/unpublish', [SellerProductController::class, 'unpublish'])->name('unpublish');

    Route::post('/{product}/files', [SellerProductFileController::class, 'storeFile'])->name('files.store');
    Route::delete('/{product}/files/{file}', [SellerProductFileController::class, 'destroyFile'])->name('files.destroy');
    Route::get('/{product}/files/{file}/download', [SellerProductFileController::class, 'downloadFile'])->name('files.download');
    Route::post('/{product}/images', [SellerProductFileController::class, 'storeImage'])->name('images.store');
    Route::delete('/{product}/images/{image}', [SellerProductFileController::class, 'destroyImage'])->name('images.destroy');
    Route::post('/{product}/images/{image}/cover', [SellerProductFileController::class, 'coverImage'])->name('images.cover');
});

// Public documents: readable by guests and signed-in users alike
Route::get('/engagements/policies/cancellation', [PolicyManagementController::class, 'index'])->name('engagements.policy');
Route::get('/payments-and-earnings', PaymentsPolicyController::class)->name('policies.payments');
Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/licence-terms', [LegalController::class, 'licences'])->name('legal.licences');

// The licences a member holds after buying models, and the certificate for each
Route::middleware('auth')->group(function () {
    Route::get('my-licences', [LicenceController::class, 'index'])->name('licences.index');
    Route::get('my-licences/{licence:key}', [LicenceController::class, 'show'])->name('licences.show');
    Route::get('my-licences/{licence:key}/files/{file}', LicenceDownloadController::class)->middleware('throttle:60,1')->name('licences.download');

    // A member's earnings and withdrawals
    Route::get('earnings', [EarningsController::class, 'index'])->name('earnings.index');
    Route::post('earnings/withdraw', [EarningsController::class, 'withdraw'])->middleware('throttle:10,1')->name('earnings.withdraw');
    Route::delete('earnings/withdrawals/{payout:reference}', [EarningsController::class, 'cancel'])->name('earnings.cancel');

    // Buying a model: start the M-Pesa payment (or take a free licence), then wait on the payment page
    Route::post('models/{product}/checkout', [CheckoutController::class, 'start'])->middleware('throttle:6,1')->name('checkout.start');
    Route::post('models/{product}/get-free', [CheckoutController::class, 'free'])->middleware('throttle:10,1')->name('checkout.free');
    Route::get('payments/{payment:reference}', [PaymentController::class, 'show'])->name('payments.show');
    Route::get('payments/{payment:reference}/status', [PaymentController::class, 'status'])->middleware('throttle:60,1')->name('payments.status');
});

// The payment gateway calls this when a payment is paid, declined or cancelled (no sign-in, no CSRF token)
Route::post('webhooks/payments/{name}', PaymentCallbackController::class)->middleware('throttle:120,1')->name('webhooks.payments');
Route::post('webhooks/payouts/{name}', PayoutCallbackController::class)->middleware('throttle:120,1')->name('webhooks.payouts');
Route::post('webhooks/payouts/{name}/timeout', PayoutTimeoutController::class)->middleware('throttle:120,1')->name('webhooks.payouts.timeout');

// Client - Freelancer Messaging
Route::middleware(['auth'])->prefix('chat')->group(function () {
    Route::get('/engagements/{engagement}/data', [MessageController::class, 'getEngagementData'])->name('engagements.data');
    Route::post('/engagements/{engagement}/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::patch('/engagements/{engagement}/messages/read', [MessageController::class, 'markAsRead'])->name('messages.read');
});

require __DIR__.'/auth.php';
