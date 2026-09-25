<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateStaffRoleRequest;
use App\Models\User;
use App\Services\Admin\StaffManagementService;

class AdminStaffController extends Controller
{
    protected $staffManagementService;

    public function __construct(StaffManagementService $staffManagementService)
    {
        $this->staffManagementService = $staffManagementService;
    }

    public function index()
    {
        $users = $this->staffManagementService->getStaffAssignableUsers();

        return view('admin.staff.index', compact('users'));
    }

    public function updateRole(UpdateStaffRoleRequest $request, User $user)
    {
        try {
            $this->staffManagementService->updateRole($user, $request->validated('role'));

            return redirect()->back()->with('success', 'Staff role updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
