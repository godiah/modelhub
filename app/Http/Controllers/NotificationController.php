<?php

namespace App\Http\Controllers;

use App\Models\ApplicantMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Auth::user()->notifications()->paginate(10);
        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        // If it's an AJAX request, return the full message
        if (request()->ajax()) {
            if ($notification->type === 'App\Notifications\NewApplicationMessage') {
                $message = ApplicantMessage::find($notification->data['message_id'] ?? null);
                return response()->json([
                    'success' => true,
                    'fullMessage' => $message ? $message->message : 'Message not found'
                ]);
            }
            return response()->json(['success' => true]);
        }

        // Regular redirect for non-AJAX requests
        if ($notification->type === 'App\Notifications\NewApplicationMessage') {
            $jobSlug = $notification->data['job_slug'];
            return redirect()->route('applications.show', $jobSlug);
        }

        return redirect()->back()->with([
            'success' => 'Notification marked as read',
            'alert' => [
                'type' => 'success',
                'title' => 'Notification marked as read',
                'text' => 'Notification marked as read',
            ]
        ]);
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return redirect()->back()->with([
            'success' => 'All notifications marked as read',
            'alert' => [
                'type' => 'success',
                'title' => 'All notifications marked as read',
                'text' => 'All notifications marked as read',
            ]
        ]);
    }

    public function destroy($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Notification deleted');
    }

    public function destroyAll()
    {
        Auth::user()->notifications()->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'All notifications cleared');
    }
}
