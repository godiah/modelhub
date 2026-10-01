<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\FlashAlertHelper;
use App\Http\Controllers\Controller;
use App\Models\ModelJob;
use App\Services\Admin\ProjectDirectoryService;
use App\Services\Admin\ProjectModerationService;
use Illuminate\Http\Request;

/** Every project on the board, and taking one down. Permissions: view projects, moderate projects. */
class AdminProjectController extends Controller
{
    public function __construct(protected ProjectDirectoryService $directory, protected ProjectModerationService $moderation) {}

    public function index(Request $request)
    {
        $status = array_key_exists($request->query('status'), ProjectDirectoryService::STATUSES) ? $request->query('status') : 'all';
        $term = trim((string) $request->query('q'));

        return view('admin.projects.index', [
            'projects' => $this->directory->query($status, $term)->paginate(12)->withQueryString(),
            'status' => $status, 'term' => $term, 'counts' => $this->directory->counts(),
        ]);
    }

    public function show(ModelJob $job)
    {
        $job->load(['user:id,name,avatar', 'takenDownBy:id,name', 'applications.applicant:id,name,avatar', 'engagements.application:id,applicant_id']);

        return view('admin.projects.show', ['job' => $job]);
    }

    public function takeDown(Request $request, ModelJob $job)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);

        if ($error = $this->moderation->takeDown($job, $request->user(), $data['reason'])) {
            return back()->with(FlashAlertHelper::error('Cannot do that', $error));
        }

        return back()->with(FlashAlertHelper::success('Project taken down', 'It is off the board and the poster has been told why.'));
    }

    public function restore(Request $request, ModelJob $job)
    {
        if ($error = $this->moderation->restore($job, $request->user())) {
            return back()->with(FlashAlertHelper::error('Cannot do that', $error));
        }

        return back()->with(FlashAlertHelper::success('Project restored', 'The poster has been told.'));
    }
}
