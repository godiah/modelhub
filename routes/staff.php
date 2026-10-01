<?php

use App\Http\Controllers\Admin\AdminAccountController;
use App\Http\Controllers\Admin\AdminActivityController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminDisputeController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminReviewController;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\AdminSellerController;
use App\Http\Controllers\Admin\AdminStaffController;
use App\Http\Controllers\Admin\Auth\StaffLoginController;
use App\Http\Controllers\Admin\Auth\StaffPasswordController;
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
        Route::get('notifications', [AdminNotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/read', [AdminNotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::get('notifications/{id}', [AdminNotificationController::class, 'open'])->name('notifications.open');

        Route::middleware('can:view disputes')->group(function () {
            Route::get('disputes', [AdminDisputeController::class, 'index'])->name('disputes.index');
            Route::get('disputes/{cancellation}', [AdminDisputeController::class, 'show'])->name('disputes.show');
            Route::get('disputes/{dispute}/evidence/{index}', [AdminDisputeController::class, 'evidence'])->whereNumber('index')->name('disputes.evidence');
        });
        Route::middleware('can:resolve disputes')->group(function () {
            Route::post('disputes/{dispute}/assign', [AdminDisputeController::class, 'assign'])->name('disputes.assign');
            Route::post('disputes/{dispute}/resolve', [AdminDisputeController::class, 'resolve'])->name('disputes.resolve');
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
            Route::post('staff/{staff}/invite', [AdminStaffController::class, 'invite'])->middleware('throttle:6,1')->name('staff.invite');
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
