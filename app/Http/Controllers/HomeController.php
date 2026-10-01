<?php

namespace App\Http\Controllers;

use App\Services\Jobs\JobBrowsingService;

/** The public landing page (guests only; signed-in users go straight to their dashboard). */
class HomeController extends Controller
{
    public function __invoke(JobBrowsingService $jobs)
    {
        return view('welcome', ['projects' => $jobs->latestOpenProjects(6)]);
    }
}
