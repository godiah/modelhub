<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EngagementStatus;
use App\Http\Controllers\Controller;
use App\Models\JobEngagement;
use Illuminate\Http\Request;

/** Read-only oversight of hires. Permission: view engagements. Private messages are never shown here. */
class AdminEngagementController extends Controller
{
    public function index(Request $request)
    {
        $statuses = collect(EngagementStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all();
        $status = array_key_exists($request->query('status'), $statuses) ? $request->query('status') : 'all';
        $term = trim((string) $request->query('q'));

        $engagements = JobEngagement::with(['application.job:id,title', 'application.poster:id,name,avatar', 'application.applicant:id,name,avatar'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($term !== '', fn ($query) => $query->whereHas('application', fn ($a) => $a
                ->whereHas('job', fn ($j) => $j->where('title', 'like', "%{$term}%"))
                ->orWhereHas('poster', fn ($u) => $u->where('name', 'like', "%{$term}%"))
                ->orWhereHas('applicant', fn ($u) => $u->where('name', 'like', "%{$term}%"))))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $byStatus = JobEngagement::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.engagements.index', ['engagements' => $engagements, 'statuses' => $statuses, 'status' => $status, 'term' => $term, 'counts' => $byStatus]);
    }

    public function show(JobEngagement $engagement)
    {
        $engagement->load(['application.job:id,title,slug,budget', 'application.poster:id,name,avatar', 'application.applicant:id,name,avatar', 'deliverables', 'cancellation.dispute', 'partialPayments']);

        return view('admin.engagements.show', ['engagement' => $engagement]);
    }
}
