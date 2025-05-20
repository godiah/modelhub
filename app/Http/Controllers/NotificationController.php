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
            } elseif ($notification->type === 'App\Notifications\EngagementResponseNotification') {
                return response()->json([
                    'success' => true,
                    'fullMessage' => $notification->data['notes'] ?? 'No additional notes provided.'
                ]);
            } elseif ($notification->type === 'App\Notifications\EngagementCancelledNotification') {
                return response()->json([
                    'success' => true,
                    'fullMessage' => $notification->data['reason_details'] ?? 'No cancellation details provided.'
                ]);
            }
            return response()->json(['success' => true]);
        }

        // Regular redirect for non-AJAX requests
        if ($notification->type === 'App\Notifications\NewApplicationMessage') {
            $jobSlug = $notification->data['job_slug'];
            return redirect()->route('applications.show', $jobSlug);
        } elseif ($notification->type === 'App\Notifications\HiredNotification') {
            $applicationId = $notification->data['application_id'];
            $jobSlug = $notification->data['job_slug'];
            return redirect()->route('engagements.response-form', $applicationId);
        } elseif ($notification->type === 'App\Notifications\EngagementResponseNotification') {
            $applicationId = $notification->data['application_id'];
            $jobSlug = $notification->data['job_slug'];
            return redirect()->route('engagements.index');
        } elseif ($notification->type === 'App\Notifications\EngagementCancelledNotification') {
            $engagementId = $notification->data['engagement_id'];
            return redirect()->route('engagements.index');
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
