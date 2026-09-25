<?php

namespace App\Http\Controllers;

use App\Http\Requests\MessageTemplate\StoreMessageTemplateRequest;
use App\Http\Resources\MessageTemplateResource;
use App\Models\MessageTemplate;
use Illuminate\Support\Facades\Auth;

class MessageTemplateController extends Controller
{
    public function index()
    {
        // Get both user-specific templates and global templates (with null user_id)
        $userTemplates = MessageTemplate::where('user_id', Auth::id())->get();
        $globalTemplates = MessageTemplate::whereNull('user_id')->get();

        // Merge both collections
        $templates = $userTemplates->merge($globalTemplates);

        return MessageTemplateResource::collection($templates);
    }

    public function store(StoreMessageTemplateRequest $request)
    {
        $template = MessageTemplate::create($request->getTemplateData());

        return new MessageTemplateResource($template);
    }

    public function destroy(MessageTemplate $template)
    {
        // Check if the template belongs to the authenticated user
        if ($template->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action. You can only delete your own templates.');
        }

        $template->delete();

        return response()->json(['success' => true]);
    }
}
