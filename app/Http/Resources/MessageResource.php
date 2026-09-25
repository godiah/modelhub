<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'sender_name' => $this->sender->name ?? 'Unknown',
            'is_own' => $this->sender_id === Auth::id(),
            'created_at' => $this->created_at->toISOString(),
            'read_at' => $this->read_at?->toISOString(),
        ];
    }
}
