<?php

use App\Http\Controllers\Support\SupportReadController;
use Illuminate\Support\Facades\Route;

/*
 * The read API for the support assistant, mounted under /api/support/v1 with the `support.reads` middleware group (see bootstrap/app.php):
 * no session, no CSRF, its own signature and claim checks. GET only. Which member is asked about comes from the signed claim, never the URL.
 */

Route::get('ping', [SupportReadController::class, 'ping'])->name('ping');

Route::middleware('support.capability:withdrawals')->group(function () {
    Route::get('withdrawals', [SupportReadController::class, 'withdrawals'])->name('withdrawals.index');
    Route::get('withdrawals/{reference}', [SupportReadController::class, 'withdrawal'])->name('withdrawals.show');
});

Route::middleware('support.capability:payments')->group(function () {
    Route::get('payments', [SupportReadController::class, 'payments'])->name('payments.index');
    Route::get('payments/{reference}', [SupportReadController::class, 'payment'])->name('payments.show');
});

Route::get('balance', [SupportReadController::class, 'balance'])->middleware('support.capability:balance')->name('balance.show');

Route::get('licences', [SupportReadController::class, 'licences'])->middleware('support.capability:licences')->name('licences.index');
