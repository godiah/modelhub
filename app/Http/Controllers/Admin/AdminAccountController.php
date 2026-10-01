<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\FlashAlertHelper;
use App\Http\Controllers\Controller;
use App\Support\Auth\PasswordPolicy;
use App\Support\Avatars;
use App\Support\Staff\StaffAudit;
use Illuminate\Http\Request;

/** A staff member's own account: their name, avatar and password. */
class AdminAccountController extends Controller
{
    public function edit(Request $request)
    {
        return view('admin.account.edit', ['staff' => $request->user()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'min:2', 'max:100']]);
        $request->user()->update($data);

        return back()->with(FlashAlertHelper::success('Account updated'));
    }

    public function avatar(Request $request)
    {
        $request->validate(['avatar' => ['required', 'string', fn ($attribute, $value, $fail) => Avatars::isValid(Avatars::PEOPLE, $value) || $fail('Choose an avatar from the list.')]]);
        $request->user()->update(['avatar' => $request->input('avatar')]);

        return back()->with(FlashAlertHelper::success('Avatar updated'));
    }

    public function password(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password:staff'],
            'password' => ['required', 'confirmed', PasswordPolicy::rule(staff: true), 'different:current_password'],
        ]);

        $request->user()->update(['password' => $request->input('password')]);
        StaffAudit::log('staff.password-changed', 'Changed their password', $request->user());

        return back()->with(FlashAlertHelper::success('Password changed'));
    }
}
