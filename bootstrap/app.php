<?php

use App\Console\Commands\DeactivateExpiredJobs;
use App\Http\Middleware\ApplySessionSettings;
use App\Http\Middleware\EnforceSessionRules;
use App\Http\Middleware\EnsureApprovedSeller;
use App\Http\Middleware\EnsureMemberActive;
use App\Http\Middleware\EnsureStaffActive;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\ResetDefaultGuard;
use App\Http\Middleware\Support\AuditSupportRead;
use App\Http\Middleware\Support\SupportReadsGate;
use App\Http\Middleware\Support\VerifySupportMember;
use App\Http\Middleware\Support\VerifySupportSignature;
use App\Http\Middleware\VerifyJobEngagementOwnership;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        then: function () {
            // The staff portal: its own routes, its own sign-in, under /admin
            Route::middleware('web')->group(base_path('routes/staff.php'));

            // The support assistant's read API: its own middleware group, not `web` (no session or CSRF) and not `api`
            Route::middleware('support.reads')->prefix('api/support/v1')->name('support.api.')->group(base_path('routes/support_api.php'));
        },
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Set from JS by the sidebar collapse toggle; read server-side so the shell renders in the right state.
        $middleware->encryptCookies(except: ['modelhub_sidebar']);

        // The payment gateway posts to us without a session, so its callback carries no CSRF token
        $middleware->validateCsrfTokens(except: ['webhooks/*']);

        $middleware->web(prepend: [ResetDefaultGuard::class, ApplySessionSettings::class], append: [EnforceSessionRules::class, EnsureMemberActive::class]);

        // Order matters: switch and address, then audit wrapping the rest, then signature, then who the call is about
        $middleware->group('support.reads', [
            SupportReadsGate::class,
            AuditSupportRead::class,
            VerifySupportSignature::class,
            VerifySupportMember::class,
        ]);

        $middleware->alias([
            'verify-engagement-ownership' => VerifyJobEngagementOwnership::class,
            'seller' => EnsureApprovedSeller::class,
            'staff.active' => EnsureStaffActive::class,
            'super-admin' => EnsureSuperAdmin::class,
        ]);

        // Members and staff sign in separately: anything under /admin sends guests to the staff sign-in
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('admin', 'admin/*') ? route('admin.login') : route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => $request->is('admin', 'admin/*') ? route('admin.dashboard') : route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withCommands([
        DeactivateExpiredJobs::class,
    ])->create();
