<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A reply staff use again and again. Shared with the team (no owner) or personal (only its owner sees it). The wording is the team's: it holds
 * placeholders such as {member_name} that are filled from the ticket when it is inserted (see App\Services\Support\Tickets\SavedReplies).
 */
class SupportSavedReply extends Model
{
    public const TOPICS = ['Payments', 'Withdrawals', 'Refunds', 'Account access', 'General'];

    protected $fillable = ['title', 'topic', 'body', 'owner_id', 'updated_by', 'uses'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'owner_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'updated_by');
    }

    public function isShared(): bool
    {
        return $this->owner_id === null;
    }

    /** What this staff member may see and use: the team's replies and their own. Someone else's personal reply does not exist as far as they can tell. */
    public function scopeVisibleTo(Builder $query, Staff $staff): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull('owner_id')->orWhere('owner_id', $staff->getKey()));
    }
}
