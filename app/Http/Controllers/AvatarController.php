<?php

namespace App\Http\Controllers;

use App\Helpers\FlashAlertHelper;
use App\Support\Avatars;
use Illuminate\Http\Request;

/** A member picks their avatar from the catalogue. (A store's is chosen with the rest of its settings.) */
class AvatarController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'string', fn ($attribute, $value, $fail) => Avatars::isValid(Avatars::PEOPLE, $value) || $fail('Choose an avatar from the list.')],
        ]);

        $request->user()->update(['avatar' => $request->input('avatar')]);

        return redirect()->to(route('profile').'#profile')->with(FlashAlertHelper::success('Avatar updated', 'This is how you appear across ModelHub.'));
    }
}
