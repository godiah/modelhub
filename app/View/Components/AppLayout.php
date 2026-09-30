<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * @param  string|null  $title  Page heading for the public (guest) shell only; the signed-in shell relies on the breadcrumb.
     * @param  string|null  $crumb  Trailing breadcrumb segment for detail pages (a project title, "Archived", ...).
     */
    public function __construct(
        public ?string $title = null,
        public ?string $crumb = null,
    ) {}

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.app');
    }
}
