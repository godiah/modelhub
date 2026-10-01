<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/** The staff portal's shell: a sidebar of queues and tools, and a top bar with the staff member's account menu. */
class StaffLayout extends Component
{
    /** @param  string|null  $title  The page heading, shown in the top bar and the browser tab. */
    public function __construct(public ?string $title = null) {}

    public function render(): View
    {
        return view('layouts.staff');
    }
}
