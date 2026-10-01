<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\StaffSearchService;
use Illuminate\Http\Request;

/** The palette's search: JSON results grouped by area, limited to what the signed-in staff member may view. */
class AdminSearchController extends Controller
{
    public function __invoke(Request $request, StaffSearchService $search)
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);

        return response()->json(['groups' => $search->search($request->user(), (string) $request->query('q'))]);
    }
}
