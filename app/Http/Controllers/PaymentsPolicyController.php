<?php

namespace App\Http\Controllers;

use App\Support\Markdown\PolicyDocument;
use Carbon\Carbon;

/**
 * "How payments and earnings work": the members' guide to fees, escrow, refunds, withdrawals and what happens when something goes wrong. The text is
 * the markdown file resources/policies/payments.md. Drafting notes in it ("[CONFIRM: ...]") are shown to reviewers outside production only.
 */
class PaymentsPolicyController extends Controller
{
    public function __invoke()
    {
        $page = PolicyDocument::render(file_get_contents(resource_path('policies/payments.md')), showNotes: ! app()->isProduction());

        return view('legal.payments', [
            'title' => $page['title'], 'intro' => $page['intro'], 'sections' => $page['sections'],
            'updated' => Carbon::parse(config('legal.payments_policy_updated'))->format('F j, Y'),
        ]);
    }
}
