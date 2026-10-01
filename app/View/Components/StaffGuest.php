<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/** The staff sign-in pages' shell: a centred card on a dark page. */
class StaffGuest extends Component
{
    public function render(): View
    {
        return view('staff.layouts.guest');
    }
}
