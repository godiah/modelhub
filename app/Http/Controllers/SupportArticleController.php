<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ForwardsToSupportAgent;
use App\Services\Support\SupportAgentClient;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Opens the help article behind a source under an answer. The slug is limited by the route to letters, digits and hyphens, and the one
 * optional query value (the passage to mark) must be an id; nothing else the browser adds is passed on.
 */
class SupportArticleController extends Controller
{
    use ForwardsToSupportAgent;

    public function show(Request $request, SupportAgentClient $agent, string $slug): Response
    {
        $validated = $request->validate(['chunk' => ['nullable', 'uuid']]);

        return $this->forward(fn (string $requestId) => $agent->article($request->user(), $request->session()->getId(), $slug, $validated['chunk'] ?? null, $requestId));
    }
}
