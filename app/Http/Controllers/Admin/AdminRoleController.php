<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\FlashAlertHelper;
use App\Http\Controllers\Controller;
use App\Support\Staff\StaffAccess;
use App\Support\Staff\StaffAudit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

/** Roles: what each one is called, what it is for, and which permissions it grants. Permission: manage roles. */
class AdminRoleController extends Controller
{
    public function index()
    {
        StaffAccess::ensurePermissions();

        return view('admin.roles.index', [
            'roles' => Role::where('guard_name', StaffAccess::GUARD)->withCount(['permissions', 'users'])->orderByRaw('name = ? desc', [StaffAccess::SUPER_ADMIN])->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.roles.form', ['role' => null, 'granted' => [], 'catalogue' => StaffAccess::catalogue()]);
    }

    public function store(Request $request)
    {
        StaffAccess::ensurePermissions();
        $data = $this->validated($request);

        $role = Role::create(['name' => $data['name'], 'guard_name' => StaffAccess::GUARD, 'description' => $data['description'] ?? null]);
        $role->syncPermissions($data['permissions'] ?? []);
        StaffAudit::log('role.created', "Created the role {$role->name}", null, ['permissions' => $data['permissions'] ?? []]);

        return redirect()->route('admin.roles.index')->with(FlashAlertHelper::success('Role created', "{$role->name} can now be given to staff."));
    }

    public function edit(Role $role)
    {
        $this->forStaffGuard($role);

        return view('admin.roles.form', ['role' => $role, 'granted' => $role->permissions->pluck('name')->all(), 'catalogue' => StaffAccess::catalogue()]);
    }

    public function update(Request $request, Role $role)
    {
        $this->forStaffGuard($role);
        abort_if($role->name === StaffAccess::SUPER_ADMIN, 403, 'The Super admin role cannot be changed.');
        StaffAccess::ensurePermissions();
        $data = $this->validated($request, $role);

        $before = $role->permissions->pluck('name')->sort()->values()->all();
        $role->update(['name' => $data['name'], 'description' => $data['description'] ?? null]);
        $role->syncPermissions($data['permissions'] ?? []);
        StaffAudit::log('role.updated', "Changed the role {$role->name}", null, ['from' => $before, 'to' => collect($data['permissions'] ?? [])->sort()->values()->all()]);

        return redirect()->route('admin.roles.index')->with(FlashAlertHelper::success('Role updated'));
    }

    public function destroy(Role $role)
    {
        $this->forStaffGuard($role);
        abort_if($role->name === StaffAccess::SUPER_ADMIN, 403, 'The Super admin role cannot be deleted.');

        if ($role->users()->exists()) {
            return back()->with(FlashAlertHelper::error('Cannot delete this role', 'Staff still hold it. Take it off them first.'));
        }

        $role->delete();
        StaffAudit::log('role.deleted', "Deleted the role {$role->name}");

        return redirect()->route('admin.roles.index')->with(FlashAlertHelper::success('Role deleted'));
    }

    private function forStaffGuard(Role $role): void
    {
        abort_unless($role->guard_name === StaffAccess::GUARD, 404);
    }

    private function validated(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:60', Rule::unique('roles', 'name')->where('guard_name', StaffAccess::GUARD)->ignore($role?->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(StaffAccess::permissions())],
        ], ['name.unique' => 'There is already a role with that name.']);
    }
}
