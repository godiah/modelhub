<?php

/**
 * MessagingService
 *
 * Handles the per-engagement chat: loading a conversation for display, sending a
 * message, and marking the other party's messages as read.
 */

namespace App\Services\Messaging;

use App\Http\Resources\MessageResource;
use App\Models\JobEngagement;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;

class MessagingService
{
    public function getChatData(JobEngagement $engagement): array
    {
        $engagement->load(['application.job', 'application.applicant', 'application.poster']);

        $messages = Message::where('engagement_id', $engagement->id)
            ->with('sender:id,name')
            ->orderBy('created_at')
            ->get();

        $isPoster = Auth::id() === $engagement->application->poster_id;
        $otherUser = $isPoster ? $engagement->application->applicant : $engagement->application->poster;

        return [
            'engagement' => [
                'id' => $engagement->id,
                'job_title' => $engagement->application->job->title ?? 'Untitled Job',
                'other_user_name' => $otherUser->name ?? ($isPoster ? 'Freelancer' : 'Client'),
                'other_user_initials' => $otherUser?->getInitials() ?? '??',
                'status' => $engagement->status,
            ],
            'messages' => MessageResource::collection($messages)->resolve(),
        ];
    }

    public function sendMessage(JobEngagement $engagement, string $content): Message
    {
        $message = $engagement->messages()->create([
            'sender_id' => Auth::id(),
            'content' => $content,
        ]);

        return $message->load('sender:id,name');
    }

    public function markAsRead(JobEngagement $engagement): int
    {
        return Message::where('engagement_id', $engagement->id)
            ->where('sender_id', '!=', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
