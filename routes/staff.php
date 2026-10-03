<?php

use App\Http\Controllers\Admin\AdminAccountController;
use App\Http\Controllers\Admin\AdminActivityController;
use App\Http\Controllers\Admin\AdminBulkController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminDisputeController;
use App\Http\Controllers\Admin\AdminEngagementController;
use App\Http\Controllers\Admin\AdminEscrowRefundController;
use App\Http\Controllers\Admin\AdminHandbookController;
use App\Http\Controllers\Admin\AdminLedgerController;
use App\Http\Controllers\Admin\AdminMemberController;
use App\Http\Controllers\Admin\AdminModelDirectoryController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\AdminOverviewController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminPayoutController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminProjectController;
use App\Http\Controllers\Admin\AdminReviewController;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\AdminSearchController;
use App\Http\Controllers\Admin\AdminSellerController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\AdminStaffController;
use App\Http\Controllers\Admin\AdminStoreController;
use App\Http\Controllers\Admin\Auth\StaffLoginController;
use App\Http\Controllers\Admin\Auth\StaffPasswordController;
use App\Http\Controllers\Auth\AuthenticatorController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| The staff portal (/admin)
|--------------------------------------------------------------------------
|
| Staff have their own accounts and their own session guard ("staff"), apart from members. Every page below the sign-in
| needs a signed-in, active staff member, and most need a permission (checked with `can:`, which Super admins always pass).
|
*/

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:staff')->group(function () {
        Route::get('login', [StaffLoginController::class, 'create'])->name('login');
        Route::post('login', [StaffLoginController::class, 'store'])->middleware('throttle:20,1')->name('login.store');
        Route::get('two-factor', [TwoFactorChallengeController::class, 'show'])->name('two-factor.challenge');
        Route::post('two-factor', [TwoFactorChallengeController::class, 'store'])->middleware('throttle:20,1')->name('two-factor.verify');
        Route::post('two-factor/resend', [TwoFactorChallengeController::class, 'resend'])->middleware('throttle:6,1')->name('two-factor.resend');
        Route::post('two-factor/cancel', [TwoFactorChallengeController::class, 'cancel'])->name('two-factor.cancel');
        Route::get('forgot-password', [StaffPasswordController::class, 'request'])->name('password.request');
        Route::post('forgot-password', [StaffPasswordController::class, 'email'])->middleware('throttle:6,1')->name('password.email');
        Route::get('reset-password/{token}', [StaffPasswordController::class, 'reset'])->name('password.reset');
        Route::post('reset-password', [StaffPasswordController::class, 'update'])->middleware('throttle:6,1')->name('password.update');
    });

    Route::middleware(['auth:staff', 'staff.active'])->group(function () {
        Route::post('logout', [StaffLoginController::class, 'destroy'])->name('logout');
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        // Their own account and notifications: no permission needed
        Route::get('account', [AdminAccountController::class, 'edit'])->name('account.edit');
        Route::patch('account', [AdminAccountController::class, 'update'])->name('account.update');
        Route::patch('account/avatar', [AdminAccountController::class, 'avatar'])->name('account.avatar');
        Route::put('account/password', [AdminAccountController::class, 'password'])->name('account.password');
        // The staff handbooks: any signed-in staff member can read them
        Route::get('handbook/{guide}', [AdminHandbookController::class, 'show'])->name('handbook.show');

        Route::prefix('account/authenticator')->name('account.authenticator.')->group(function () {
            Route::post('/', [AuthenticatorController::class, 'start'])->middleware('throttle:10,1')->name('start');
            Route::post('confirm', [AuthenticatorController::class, 'confirm'])->middleware('throttle:10,1')->name('confirm');
            Route::delete('setup', [AuthenticatorController::class, 'cancel'])->name('cancel');
            Route::post('recovery-codes', [AuthenticatorController::class, 'recoveryCodes'])->middleware('throttle:10,1')->name('recovery');
            Route::delete('/', [AuthenticatorController::class, 'destroy'])->middleware('throttle:10,1')->name('destroy');
        });
        // Bulk actions from the lists; the permission for each action is checked inside
        Route::post('bulk/{action}', AdminBulkController::class)->where('action', '[a-z]+\.[a-z]+')->middleware('throttle:30,1')->name('bulk');
        Route::get('search', AdminSearchController::class)->middleware('throttle:60,1')->name('search');
        Route::get('notifications', [AdminNotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/read', [AdminNotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::get('notifications/{id}', [AdminNotificationController::class, 'open'])->name('notifications.open');

        Route::middleware('can:view platform overview')->get('overview', AdminOverviewController::class)->name('overview');

        Route::middleware('can:view members')->group(function () {
            Route::get('members', [AdminMemberController::class, 'index'])->name('members.index');
            Route::get('members/{member}', [AdminMemberController::class, 'show'])->name('members.show');
        });
        Route::middleware('can:manage members')->group(function () {
            Route::post('members/{member}/suspend', [AdminMemberController::class, 'suspend'])->name('members.suspend');
            Route::post('members/{member}/reinstate', [AdminMemberController::class, 'reinstate'])->name('members.reinstate');
            Route::post('members/{member}/notes', [AdminMemberController::class, 'note'])->name('members.notes.store');
            Route::post('members/{member}/password-reset', [AdminMemberController::class, 'passwordReset'])->middleware('throttle:6,1')->name('members.password-reset');
            Route::post('members/{member}/two-factor-reset', [AdminMemberController::class, 'twoFactorReset'])->middleware('throttle:6,1')->name('members.two-factor-reset');
            Route::post('members/{member}/verification', [AdminMemberController::class, 'verification'])->middleware('throttle:6,1')->name('members.verification');
        });

        Route::middleware('can:view projects')->group(function () {
            Route::get('projects', [AdminProjectController::class, 'index'])->name('projects.index');
            Route::get('projects/{job}', [AdminProjectController::class, 'show'])->name('projects.show');
        });
        Route::middleware('can:moderate projects')->group(function () {
            Route::post('projects/{job}/take-down', [AdminProjectController::class, 'takeDown'])->name('projects.take-down');
            Route::post('projects/{job}/restore', [AdminProjectController::class, 'restore'])->name('projects.restore');
        });

        Route::middleware('can:view engagements')->group(function () {
            Route::get('engagements', [AdminEngagementController::class, 'index'])->name('engagements.index');
            Route::get('engagements/{engagement}', [AdminEngagementController::class, 'show'])->name('engagements.show');
        });

        Route::middleware('can:view models')->group(function () {
            Route::get('catalogue', [AdminModelDirectoryController::class, 'index'])->name('catalogue.index');
            Route::get('catalogue/{product}', [AdminModelDirectoryController::class, 'show'])->name('catalogue.show');
        });

        Route::middleware('can:view sellers')->group(function () {
            Route::get('stores', [AdminStoreController::class, 'index'])->name('stores.index');
            Route::get('stores/{seller}', [AdminStoreController::class, 'show'])->name('stores.show');
        });

        Route::patch('stores/{seller}/commission', [AdminStoreController::class, 'commission'])->middleware('super-admin')->name('stores.commission');

        Route::middleware('can:view disputes')->group(function () {
            Route::get('disputes', [AdminDisputeController::class, 'index'])->name('disputes.index');
            Route::get('disputes/{cancellation}', [AdminDisputeController::class, 'show'])->name('disputes.show');
            Route::get('disputes/{dispute}/evidence/{index}', [AdminDisputeController::class, 'evidence'])->whereNumber('index')->name('disputes.evidence');
        });
        Route::middleware('can:resolve disputes')->group(function () {
            Route::post('disputes/{dispute}/assign', [AdminDisputeController::class, 'assign'])->name('disputes.assign');
            Route::post('disputes/{dispute}/resolve', [AdminDisputeController::class, 'resolve'])->name('disputes.resolve');
        });

        Route::middleware('can:view payments')->group(function () {
            Route::get('payments', [AdminPaymentController::class, 'index'])->name('payments.index');
            Route::get('payments/{payment:reference}', [AdminPaymentController::class, 'show'])->name('payments.show');
            Route::get('escrow-refunds', [AdminEscrowRefundController::class, 'index'])->name('escrow-refunds.index');
        });
        Route::middleware('can:refund payments')->post('escrow-refunds/{engagement}', [AdminEscrowRefundController::class, 'refund'])->whereNumber('engagement')->middleware('throttle:20,1')->name('escrow-refunds.refund');
        Route::middleware('can:refund payments')->post('payments/{payment:reference}/refund', [AdminPaymentController::class, 'refund'])->middleware('throttle:20,1')->name('payments.refund');
        Route::middleware('can:view ledger')->group(function () {
            Route::get('ledger', [AdminLedgerController::class, 'index'])->name('ledger.index');
            Route::get('ledger/{transaction}', [AdminLedgerController::class, 'show'])->whereNumber('transaction')->name('ledger.show');
        });

        Route::middleware('can:view payouts')->get('payouts', [AdminPayoutController::class, 'index'])->name('payouts.index');
        Route::middleware('can:approve payouts')->group(function () {
            Route::post('payouts/{payout:reference}/approve', [AdminPayoutController::class, 'approve'])->middleware('throttle:30,1')->name('payouts.approve');
            Route::post('payouts/{payout:reference}/reject', [AdminPayoutController::class, 'reject'])->name('payouts.reject');
            Route::post('payouts/{payout:reference}/settle', [AdminPayoutController::class, 'settle'])->middleware('throttle:30,1')->name('payouts.settle');
        });

        Route::middleware('can:review sellers')->group(function () {
            Route::get('sellers', [AdminSellerController::class, 'index'])->name('sellers.index');
            Route::patch('sellers/{seller}/{decision}', [AdminSellerController::class, 'review'])->whereIn('decision', ['approve', 'reject', 'suspend'])->name('sellers.review');
        });

        Route::middleware('can:moderate reviews')->group(function () {
            Route::get('reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
            Route::post('reviews/{review}/hide', [AdminReviewController::class, 'hide'])->name('reviews.hide');
            Route::post('reviews/{review}/restore', [AdminReviewController::class, 'restore'])->name('reviews.restore');
            Route::post('reviews/{review}/dismiss', [AdminReviewController::class, 'dismiss'])->name('reviews.dismiss');
            Route::delete('reviews/{review}/reply', [AdminReviewController::class, 'removeReply'])->name('reviews.reply.remove');
        });

        Route::middleware('can:review models')->group(function () {
            Route::get('models', [AdminProductController::class, 'index'])->name('models.index');
            Route::patch('models/{product}/{decision}', [AdminProductController::class, 'review'])->whereIn('decision', ['publish', 'reject', 'takedown'])->name('models.review');
            Route::get('models/{product}/files/{file}', [AdminProductController::class, 'download'])->name('models.files.download');
        });

        Route::middleware('can:manage staff')->group(function () {
            Route::get('staff', [AdminStaffController::class, 'index'])->name('staff.index');
            Route::get('staff/create', [AdminStaffController::class, 'create'])->name('staff.create');
            Route::post('staff', [AdminStaffController::class, 'store'])->name('staff.store');
            Route::get('staff/{staff}', [AdminStaffController::class, 'edit'])->name('staff.edit');
            Route::patch('staff/{staff}', [AdminStaffController::class, 'update'])->name('staff.update');
            Route::post('staff/{staff}/deactivate', [AdminStaffController::class, 'deactivate'])->name('staff.deactivate');
            Route::post('staff/{staff}/reactivate', [AdminStaffController::class, 'reactivate'])->name('staff.reactivate');
            Route::post('staff/{staff}/two-factor-reset', [AdminStaffController::class, 'twoFactorReset'])->middleware('throttle:6,1')->name('staff.two-factor-reset');
            Route::post('staff/{staff}/invite', [AdminStaffController::class, 'invite'])->middleware('throttle:6,1')->name('staff.invite');
        });

        // Platform settings: Super admins only (no permission to hand out)
        Route::middleware('super-admin')->prefix('settings')->name('settings.')->group(function () {
            Route::redirect('/', '/admin/settings/security')->name('index');
            Route::get('security', [AdminSettingsController::class, 'security'])->name('security');
            Route::patch('security', [AdminSettingsController::class, 'updateSecurity'])->name('security.update');
            Route::get('fees', [AdminSettingsController::class, 'fees'])->name('fees');
            Route::patch('fees', [AdminSettingsController::class, 'updateFees'])->name('fees.update');
        });

        Route::middleware('can:manage roles')->group(function () {
            Route::get('roles', [AdminRoleController::class, 'index'])->name('roles.index');
            Route::get('roles/create', [AdminRoleController::class, 'create'])->name('roles.create');
            Route::post('roles', [AdminRoleController::class, 'store'])->name('roles.store');
            Route::get('roles/{role}', [AdminRoleController::class, 'edit'])->name('roles.edit');
            Route::patch('roles/{role}', [AdminRoleController::class, 'update'])->name('roles.update');
            Route::delete('roles/{role}', [AdminRoleController::class, 'destroy'])->name('roles.destroy');
        });

        Route::middleware('can:view audit log')->group(function () {
            Route::get('activity', [AdminActivityController::class, 'index'])->name('activity.index');
        });
    });
});
