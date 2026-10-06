<?php

use App\Http\Controllers\Support\SupportReadController;
use Illuminate\Support\Facades\Route;

/*
 * The read API for the support assistant, mounted under /api/support/v1 with the `support.reads` middleware group (see bootstrap/app.php):
 * no session, no CSRF, its own signature and claim checks. GET only. Which member is asked about comes from the signed claim, never the URL.
 */

Route::get('ping', [SupportReadController::class, 'ping'])->name('ping');
