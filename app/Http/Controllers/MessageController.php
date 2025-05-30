<?php

namespace App\Http\Controllers;

use App\Events\NewMessageEvent;
use App\Models\JobEngagement;
use App\Models\Message;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    use AuthorizesRequests;

    /**
     * Get engagement data with messages for AJAX request.
     */
    public function getEngagementData(JobEngagement $engagement)
    {
        try {
            // Check if user is authorized to view this engagement
            $this->authorize('view', $engagement);

            // Load relationships with error checking
            $engagement->load([
                'application.applicant:id,name',
                'application.poster:id,name',
            ]);

            // Load messages separately to avoid relationship issues
            $messages = Message::where('engagement_id', $engagement->id)
                ->with('sender:id,name')
                ->orderBy('created_at', 'asc')
                ->get();

            Log::info('Messages loaded', ['count' => $messages->count()]);

            // Load job safely
            $job = null;
            if ($engagement->application && $engagement->application->job_id) {
                $job = \App\Models\ModelJob::find($engagement->application->job_id);
            }

            // Verify required data exists
            if (!$job) {
                Log::error('Job not found', ['engagement_id' => $engagement->id]);
                return response()->json(['error' => 'Job data not found'], 404);
            }

            if (!$engagement->application) {
                Log::error('Application not found', ['engagement_id' => $engagement->id]);
                return response()->json(['error' => 'Application data not found'], 404);
            }

            // Determine the other user's name safely
            $otherUserName = 'Unknown User';
            $otherUserInitials = '??';
            $currentUserId = Auth::id();

            if ($currentUserId === $engagement->client_id) {
                $otherUserName = optional($engagement->application->applicant)->name ?? 'Freelancer';
                $otherUserInitials = optional($engagement->application->applicant)->getInitials() ?? '??';
            } else {
                $otherUserName = optional($engagement->application->poster)->name ?? 'Client';
                $otherUserInitials = optional($engagement->application->poster)->getInitials() ?? '??';
            }

            // Format messages for frontend
            $formattedMessages = $messages->map(function ($message) use ($currentUserId) {
                return [
                    'id' => $message->id,
                    'content' => $message->content,
                    'sender_name' => $message->sender->name ?? 'Unknown',
                    'is_own' => $message->sender_id === $currentUserId,
                    'created_at' => $message->created_at->toISOString(),
                    'read_at' => $message->read_at?->toISOString(),
                ];
            });

            $responseData = [
                'engagement' => [
                    'id' => $engagement->id,
                    'job_title' => $job->title ?? 'Untitled Job',
                    'other_user_name' => $otherUserName,
                    'other_user_initials' => $otherUserInitials,
                    'status' => $engagement->status,
                ],
                'messages' => $formattedMessages->toArray(), // Ensure it's an array
            ];

            Log::info('Successfully loaded engagement data', [
                'engagement_id' => $engagement->id,
                'message_count' => $formattedMessages->count()
            ]);

            return response()->json($responseData);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::warning('Authorization failed', [
                'engagement_id' => $engagement->id,
                'user_id' => Auth::id()
            ]);
            return response()->json(['error' => 'Unauthorized access'], 403);
        } catch (\Exception $e) {
            Log::error('Error loading engagement data', [
                'engagement_id' => $engagement->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Internal server error',
                'message' => config('app.debug') ? $e->getMessage() : 'Something went wrong'
            ], 500);
        }
    }

    /**
     * Store a new message.
     */
    public function store(Request $request, JobEngagement $engagement)
    {
        try {
            // Check if user is authorized to send messages in this engagement
            $this->authorize('message', $engagement);

            $validated = $request->validate([
                'content' => 'required|string|max:5000',
            ]);

            $message = $engagement->messages()->create([
                'sender_id' => Auth::id(),
                'content' => $validated['content'],
            ]);

            // Load sender relationship for response
            $message->load('sender:id,name');

            // Return JSON response 
            return response()->json([
                'success' => true,
                'message' => [
                    'id' => $message->id,
                    'content' => $message->content,
                    'sender_name' => $message->sender->name,
                    'is_own' => true,
                    'created_at' => $message->created_at->toISOString(),
                    'read_at' => null,
                ]
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['error' => 'Unauthorized to send message'], 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Error sending message', [
                'engagement_id' => $engagement->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error', 'Failed to send message'], 500);
        }
    }

    /**
     * Mark messages as read.
     */
    public function markAsRead(JobEngagement $engagement)
    {
        try {
            // Check if user is authorized to view these messages
            $this->authorize('view', $engagement);

            // Update unread messages for this engagement where current user is NOT the sender
            $updatedCount = Message::where('engagement_id', $engagement->id)
                ->where('sender_id', '!=', Auth::id())
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            Log::info('Messages marked as read', [
                'engagement_id' => $engagement->id,
                'updated_count' => $updatedCount,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'updated_count' => $updatedCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking messages as read', [
                'engagement_id' => $engagement->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to mark messages as read'
            ], 500);
        }
    }

    /**
     * Get unread message count for all engagements.
     */
    // public function unreadCount()
    // {
    //     $user = Auth::user();

    //     if (!$user) {
    //         return response()->json(['unread_counts' => []], 401);
    //     }

    //     // Get engagements where the user is either client or freelancer
    //     $engagements = JobEngagement::where('client_id', $user->id)
    //         ->orWhere('freelancer_id', $user->id)
    //         ->pluck('id');

    //     if ($engagements->isEmpty()) {
    //         return response()->json(['unread_counts' => []]);
    //     }

    //     $unreadCounts = Message::whereIn('engagement_id', $engagements)
    //         ->where('sender_id', '!=', $user->id)
    //         ->whereNull('read_at')
    //         ->selectRaw('engagement_id, count(*) as count')
    //         ->groupBy('engagement_id')
    //         ->get()
    //         ->keyBy('engagement_id')
    //         ->map(function ($item) {
    //             return $item->count;
    //         });

    //     return response()->json([
    //         'unread_counts' => $unreadCounts,
    //     ]);
    // }
}
