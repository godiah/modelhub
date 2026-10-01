<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\FlashAlertHelper;
use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Services\Admin\StaffManagementService;
use App\Support\Staff\ListSort;
use App\Support\Staff\StaffAccess;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

/** Staff accounts: who has one, what roles they hold, and whether it is active. Permission: manage staff. */
class AdminStaffController extends Controller
{
    /** Sortable columns: sort key => the column it orders by. */
    public const SORTS = ['name' => 'name', 'seen' => 'last_login_at'];

    public function __construct(protected StaffManagementService $staff) {}

    public function index(Request $request)
    {
        $status = in_array($request->query('status'), ['active', 'inactive', 'all'], true) ? $request->query('status') : 'active';
        $term = trim((string) $request->query('q'));
        [$sort, $dir] = ListSort::resolve($request, array_keys(self::SORTS), default: 'name', descFirst: ['seen']);

        $members = Staff::with('roles:id,name')
            ->when($status !== 'all', fn ($query) => $query->where('is_active', $status === 'active'))
            ->when($term !== '', fn ($query) => $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")))
            ->tap(fn ($query) => ListSort::apply($query, $sort, $dir, self::SORTS))
            ->paginate(15)
            ->withQueryString();

        return view('admin.staff.index', [
            'members' => $members, 'status' => $status, 'term' => $term, 'sort' => $sort, 'dir' => $dir,
            'counts' => ['active' => Staff::where('is_active', true)->count(), 'inactive' => Staff::where('is_active', false)->count(), 'all' => Staff::count()],
        ]);
    }

    public function create()
    {
        return view('admin.staff.create', ['roles' => $this->roles()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:staff,email'],
            'roles' => ['array'],
            'roles.*' => ['string'],
        ], ['email.unique' => 'There is already a staff account with that email address.']);

        $member = $this->staff->invite($data['name'], $data['email'], $data['roles'] ?? []);

        return redirect()->route('admin.staff.edit', $member)->with(FlashAlertHelper::success('Invitation sent', "{$member->name} has been emailed a link to set their password."));
    }

    public function edit(Staff $staff)
    {
        return view('admin.staff.edit', ['member' => $staff->load('roles:id,name'), 'roles' => $this->roles()]);
    }

    public function update(Request $request, Staff $staff)
    {
        $data = $request->validate(['roles' => ['array'], 'roles.*' => ['string']]);

        if ($error = $this->staff->updateRoles($staff, $data['roles'] ?? [])) {
            return back()->with(FlashAlertHelper::error('Cannot change the roles', $error));
        }

        return back()->with(FlashAlertHelper::success('Roles updated'));
    }

    public function deactivate(Request $request, Staff $staff)
    {
        if ($error = $this->staff->deactivate($staff, $request->user())) {
            return back()->with(FlashAlertHelper::error('Cannot deactivate', $error));
        }

        return back()->with(FlashAlertHelper::success('Account deactivated', "{$staff->name} can no longer sign in."));
    }

    public function reactivate(Staff $staff)
    {
        $this->staff->reactivate($staff);

        return back()->with(FlashAlertHelper::success('Account reactivated'));
    }

    public function invite(Staff $staff)
    {
        $this->staff->sendInvitation($staff);

        return back()->with(FlashAlertHelper::success('Invitation sent', "A new link to set a password was emailed to {$staff->email}."));
    }

    public function twoFactorReset(Request $request, Staff $staff)
    {
        abort_if($staff->is($request->user()), 403, 'Manage your own two-step sign-in from your account page.');

        $this->staff->resetTwoFactor($staff, $request->user());

        return back()->with(FlashAlertHelper::success('Two-step sign-in reset', "{$staff->name} was emailed, and can set up a new authenticator app."));
    }

    private function roles()
    {
        return Role::where('guard_name', StaffAccess::GUARD)->orderByRaw('name = ? desc', [StaffAccess::SUPER_ADMIN])->orderBy('name')->get(['id', 'name', 'description']);
    }
}
