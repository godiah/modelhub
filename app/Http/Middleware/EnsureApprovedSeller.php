<?php

namespace App\Http\Middleware;

use App\Helpers\FlashAlertHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Only approved sellers can manage model listings; everyone else is sent to the seller page to apply or see their status. */
class EnsureApprovedSeller
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isApprovedSeller()) {
            return redirect()->route('seller.index')->with(
                FlashAlertHelper::info('Seller approval needed', 'Apply to sell, or check where your application stands.')
            );
        }

        return $next($request);
    }
}
