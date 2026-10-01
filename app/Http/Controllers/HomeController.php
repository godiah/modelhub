<?php

namespace App\Http\Controllers;

use App\Services\Jobs\JobBrowsingService;
use App\Services\Marketplace\LandingMarketplaceService;

/** The public landing page (guests only; signed-in users go straight to their dashboard). */
class HomeController extends Controller
{
    public function __invoke(JobBrowsingService $jobs, LandingMarketplaceService $marketplace)
    {
        $featured = $marketplace->models(21);

        return view('welcome', [
            'projects' => $jobs->latestOpenProjects(6),
            'categories' => $marketplace->categories(),
            'types' => $marketplace->types(),
            'formats' => $marketplace->formats(),
            'models' => $featured['models'],
            'topRated' => $featured['topRated'],
            'stores' => $marketplace->stores(),
        ]);
    }
}
