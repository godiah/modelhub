<?php

use App\Console\Commands\DeactivateExpiredJobs;
use App\Http\Middleware\EnsureApprovedSeller;
use App\Http\Middleware\EnsureStaffActive;
use App\Http\Middleware\ResetDefaultGuard;
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
        },
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Set from JS by the sidebar collapse toggle; read server-side so the shell renders in the right state.
        $middleware->encryptCookies(except: ['modelhub_sidebar']);

        $middleware->web(prepend: [ResetDefaultGuard::class]);

        $middleware->alias([
            'verify-engagement-ownership' => VerifyJobEngagementOwnership::class,
            'seller' => EnsureApprovedSeller::class,
            'staff.active' => EnsureStaffActive::class,
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
