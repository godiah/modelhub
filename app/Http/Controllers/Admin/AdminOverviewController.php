<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\PlatformStatsService;

/** Platform-wide numbers with trends. Permission: view platform overview. */
class AdminOverviewController extends Controller
{
    public function __invoke(PlatformStatsService $stats)
    {
        return view('admin.overview', ['stats' => $stats->get()]);
    }
}
