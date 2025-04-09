<?php

namespace App\Http\Controllers;

use App\Models\MessageTemplate;
use Illuminate\Http\Request;
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

        return response()->json($templates);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $template = MessageTemplate::create([
            'user_id' => Auth::id(), // Always associate with current user for regular users
            'name' => $validated['name'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
        ]);

        return response()->json($template);
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
