<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\FlashAlertHelper;
use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\JobEngagement;
use App\Models\ModelJob;
use App\Models\Product;
use App\Models\User;
use App\Services\Admin\MemberManagementService;
use App\Support\Staff\ListSort;
use App\Support\Staff\StaffAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/** The member directory and a member's page. Permissions: view members, view contact details, manage members. */
class AdminMemberController extends Controller
{
    public const STATUSES = ['active' => 'Active', 'suspended' => 'Suspended', 'unverified' => 'Unverified', 'all' => 'All'];

    public const ACTIVITY = ['all' => 'Everyone', 'sellers' => 'Sellers', 'hirers' => 'Posted a project', 'freelancers' => 'Applied to a project'];

    /** Sortable columns of the directory: sort key => the column or count alias it orders by. */
    public const SORTS = ['name' => 'name', 'joined' => 'created_at', 'seen' => 'last_login_at', 'projects' => 'jobs_count', 'applications' => 'job_applications_count', 'models' => 'products_count'];

    public function __construct(protected MemberManagementService $members) {}

    public function index(Request $request)
    {
        $status = array_key_exists($request->query('status'), self::STATUSES) ? $request->query('status') : 'all';
        $activity = array_key_exists($request->query('activity'), self::ACTIVITY) ? $request->query('activity') : 'all';
        $term = trim((string) $request->query('q'));
        $canSeeContact = $request->user()->can('view contact details');
        [$sort, $dir] = ListSort::resolve($request, array_keys(self::SORTS), default: 'joined', descFirst: ['joined', 'seen', 'projects', 'applications', 'models']);

        $members = User::query()
            ->with('sellerProfile:id,user_id,status,display_name')
            ->withCount(['jobs', 'jobApplications', 'products'])
            ->tap(fn ($query) => $this->narrowStatus($query, $status))
            ->when($activity === 'sellers', fn ($q) => $q->whereHas('sellerProfile', fn ($s) => $s->where('status', 'approved')))
            ->when($activity === 'hirers', fn ($q) => $q->has('jobs'))
            ->when($activity === 'freelancers', fn ($q) => $q->has('jobApplications'))
            // Searching by email would reveal masked addresses one letter at a time, so only people who may see them can
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")->when($canSeeContact, fn ($e) => $e->orWhere('email', 'like', "%{$term}%"))))
            ->tap(fn ($query) => ListSort::apply($query, $sort, $dir, self::SORTS))
            ->paginate(15)
            ->withQueryString();

        return view('admin.members.index', [
            'members' => $members, 'sort' => $sort, 'dir' => $dir, 'status' => $status, 'activity' => $activity, 'term' => $term, 'canSeeContact' => $canSeeContact,
            'counts' => collect(array_keys(self::STATUSES))->mapWithKeys(fn ($key) => [$key => $this->narrowStatus(User::query(), $key)->count()])->all(),
        ]);
    }

    public function show(Request $request, User $member)
    {
        $staff = $request->user();
        $canSeeContact = $staff->can('view contact details');

        // Seeing real contact details is itself recorded, at most once an hour per person and member
        if ($canSeeContact && Cache::add("staff.contact-viewed.{$staff->id}.{$member->id}", true, 3600)) {
            StaffAudit::log('member.contact-viewed', "Viewed the contact details of {$member->name}", $member);
        }

        $member->load(['profile', 'sellerProfile', 'suspendedBy:id,name', 'notes.author:id,name,avatar']);

        return view('admin.members.show', [
            'member' => $member, 'canSeeContact' => $canSeeContact,
            'counts' => [
                'projects' => $member->jobs()->count(), 'applications' => $member->jobApplications()->count(), 'models' => $member->products()->count(),
                'reviews_given' => $member->reviewsGiven()->count(), 'reviews_received' => $member->reviewsReceived()->count(),
            ],
            'projects' => ModelJob::where('user_id', $member->id)->withCount('applications')->latest()->limit(5)->get(),
            'applications' => JobApplication::with('job:id,title,slug')->where('applicant_id', $member->id)->latest()->limit(5)->get(),
            'models' => Product::where('user_id', $member->id)->latest()->limit(5)->get(),
            'engagements' => JobEngagement::with(['application.job:id,title', 'application.poster:id,name', 'application.applicant:id,name'])
                ->whereHas('application', fn ($a) => $a->where('poster_id', $member->id)->orWhere('applicant_id', $member->id))
                ->latest()->limit(5)->get(),
        ]);
    }

    public function suspend(Request $request, User $member)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);

        return $this->done($this->members->suspend($member, $request->user(), $data['reason']), 'Account suspended', "{$member->name} is signed out, cannot sign in, and has been emailed the reason.");
    }

    public function reinstate(Request $request, User $member)
    {
        return $this->done($this->members->reinstate($member, $request->user()), 'Account reinstated', "{$member->name} has been told.");
    }

    public function note(Request $request, User $member)
    {
        $data = $request->validate(['body' => ['required', 'string', 'min:2', 'max:2000']]);
        $this->members->addNote($member, $request->user(), $data['body']);

        return back()->with(FlashAlertHelper::success('Note added', 'Only staff can see it.'));
    }

    public function passwordReset(Request $request, User $member)
    {
        $this->members->sendPasswordReset($member, $request->user());

        return back()->with(FlashAlertHelper::success('Password reset link sent', 'The member chooses their own new password.'));
    }

    public function twoFactorReset(Request $request, User $member)
    {
        $this->members->resetTwoFactor($member, $request->user());

        return back()->with(FlashAlertHelper::success('Two-step sign-in reset', 'The member was emailed, and can set up a new authenticator app.'));
    }

    public function verification(Request $request, User $member)
    {
        return $this->done($this->members->resendVerification($member, $request->user()), 'Verification email sent');
    }

    private function done(?string $error, string $title, ?string $text = null)
    {
        return $error ? back()->with(FlashAlertHelper::error('Cannot do that', $error)) : back()->with(FlashAlertHelper::success($title, $text));
    }

    private function narrowStatus($query, string $status)
    {
        return match ($status) {
            'active' => $query->whereNull('suspended_at'),
            'suspended' => $query->whereNotNull('suspended_at'),
            'unverified' => $query->whereNull('email_verified_at'),
            default => $query,
        };
    }
}
