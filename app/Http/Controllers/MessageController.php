<?php

namespace App\Http\Controllers;

use App\Http\Requests\Message\StoreMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\JobEngagement;
use App\Services\Messaging\MessagingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class MessageController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected MessagingService $messagingService) {}

    /**
     * Get engagement data with messages for AJAX request.
     */
    public function getEngagementData(JobEngagement $engagement)
    {
        $this->authorize('view', $engagement);

        return response()->json($this->messagingService->getChatData($engagement));
    }

    /**
     * Store a new message.
     */
    public function store(StoreMessageRequest $request, JobEngagement $engagement)
    {
        $this->authorize('message', $engagement);

        $message = $this->messagingService->sendMessage($engagement, $request->validated('content'));

        return response()->json([
            'success' => true,
            'message' => new MessageResource($message),
        ]);
    }

    /**
     * Mark messages as read.
     */
    public function markAsRead(JobEngagement $engagement)
    {
        $this->authorize('view', $engagement);

        $updatedCount = $this->messagingService->markAsRead($engagement);

        return response()->json([
            'success' => true,
            'updated_count' => $updatedCount,
        ]);
    }
}
