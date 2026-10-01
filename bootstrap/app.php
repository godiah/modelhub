<?php

use App\Console\Commands\DeactivateExpiredJobs;
use App\Http\Middleware\EnsureApprovedSeller;
use App\Http\Middleware\VerifyJobEngagementOwnership;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Set from JS by the sidebar collapse toggle; read server-side so the shell renders in the right state.
        $middleware->encryptCookies(except: ['modelhub_sidebar']);

        $middleware->alias([
            'verify-engagement-ownership' => VerifyJobEngagementOwnership::class,
            'seller' => EnsureApprovedSeller::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withCommands([
        DeactivateExpiredJobs::class,
    ])->create();
