<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/** The public (guest) page shell: site header, optional toolbar band, content, site footer. */
class SiteLayout extends Component
{
    /**
     * @param  string|null  $title  The start of the browser tab title (pages carry their own visible heading).
     * @param  string|null  $description  Meta description.
     * @param  bool  $alpine  Load Livewire's assets (Alpine) for pages that use x-data.
     */
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public bool $alpine = false,
    ) {}

    public function render(): View
    {
        return view('layouts.site');
    }
}
