<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Markdown\PolicyDocument;

/**
 * The staff handbooks: internal how-to guides written in markdown under resources/handbook and shown inside the staff portal, linked from its
 * footer. Any signed-in staff member can read them. The file is the single source: edit it and the page changes.
 */
class AdminHandbookController extends Controller
{
    /** Handbook => its file under resources/handbook. */
    public const GUIDES = ['payments' => 'payments.md'];

    public function show(string $guide)
    {
        abort_unless(array_key_exists($guide, self::GUIDES), 404);

        $path = resource_path('handbook/'.self::GUIDES[$guide]);
        abort_unless(is_file($path), 404);

        $page = PolicyDocument::render(file_get_contents($path));

        return view('admin.handbook.show', ['guide' => $guide, 'title' => $page['title'], 'intro' => $page['intro'], 'sections' => $page['sections'], 'updated' => now()->setTimestamp(filemtime($path))]);
    }
}
