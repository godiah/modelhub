<?php

namespace App\Http\Middleware;

use App\Models\JobApplication;
use App\Models\JobEngagement;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * A custom middleware to ensure only the correct users can access these routes:
 * 
 * Route::get('/{applicationId}/respond', [JobEngagementController::class, 'showResponseForm'])->name('response-form');
 * Route::post('/{engagement}/respond', [JobEngagementController::class, 'respondToOffer'])->name('respond');
 */

class VerifyJobEngagementOwnership
{
    public function handle(Request $request, Closure $next)
    {
        // For response form route (checks application ID)
        if ($request->route('applicationId')) {
            $application = JobApplication::find($request->route('applicationId'));

            if (!$application || $application->applicant_id !== Auth::id()) {
                return redirect()->route('engagements.index')->with([
                    'error' => 'You are not authorized to access this page.',
                    'alert' => [
                        'type' => 'error',
                        'title' => 'Unauthorized',
                        'text' => 'You are not authorized to access this page.',
                    ]
                ]);
            }
        }

        // For respond route (checks engagement ID)
        if ($request->route('engagement')) {
            $engagement = JobEngagement::with('application')->find($request->route('engagement'));

            if (!$engagement || $engagement->application->applicant_id !== Auth::id()) {
                return redirect()->route('engagements.index')->with([
                    'error' => 'You are not authorized to access this page.',
                    'alert' => [
                        'type' => 'error',
                        'title' => 'Unauthorized',
                        'text' => 'You are not authorized to access this page.',
                    ]
                ]);
            }
        }

        return $next($request);
    }
}
