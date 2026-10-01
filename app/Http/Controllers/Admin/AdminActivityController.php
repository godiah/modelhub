<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\StaffActivity;
use Illuminate\Http\Request;

/** The activity log: who did what in the staff portal, and when. Permission: view audit log. */
class AdminActivityController extends Controller
{
    /** The areas the log can be narrowed to: the part of an action before its dot. */
    public const AREAS = ['model' => 'Models', 'seller' => 'Sellers', 'review' => 'Reviews', 'dispute' => 'Disputes', 'staff' => 'Staff accounts', 'role' => 'Roles'];

    public function index(Request $request)
    {
        $area = array_key_exists($request->query('area'), self::AREAS) ? $request->query('area') : null;
        $staffId = $request->integer('staff') ?: null;

        $entries = StaffActivity::with('staff:id,name,avatar')
            ->when($area, fn ($query) => $query->where('action', 'like', $area.'.%'))
            ->when($staffId, fn ($query) => $query->where('staff_id', $staffId))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.activity.index', [
            'entries' => $entries, 'area' => $area, 'staffId' => $staffId, 'areas' => self::AREAS,
            'people' => Staff::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
