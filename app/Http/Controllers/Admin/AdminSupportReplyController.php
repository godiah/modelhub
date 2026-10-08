<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\FlashAlertHelper;
use App\Http\Controllers\Controller;
use App\Models\SupportSavedReply;
use App\Services\Support\Tickets\SavedReplies;
use App\Services\Support\Tickets\TicketService;
use App\Support\Staff\StaffAudit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Saved replies: the answers the team uses again and again, written once. Everyone who answers tickets (`manage support tickets`) sees the shared
 * ones and their own; a shared reply may be improved by any of them (each change is in the activity log), a personal one only by its owner. Someone
 * else's personal reply is a 404, the same as one that does not exist. Placeholders are checked on save, and filled in from the ticket on use.
 */
class AdminSupportReplyController extends Controller
{
    public function index(Request $request)
    {
        $staff = $request->user();
        $replies = SupportSavedReply::visibleTo($staff)->with('editor:id,name')->orderBy('topic')->orderBy('title')->get();
        $selected = $request->query('reply') ? $replies->firstWhere('id', (int) $request->query('reply')) : null;

        return view('admin.support.replies.index', [
            'replies' => $replies,
            'selected' => $selected,
            'creating' => $selected === null && ($request->has('new') || $replies->isEmpty()),
            'variables' => SavedReplies::variables(),
            'topics' => SupportSavedReply::TOPICS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, creating: true);
        $reply = SupportSavedReply::create([
            'title' => $data['title'], 'topic' => $data['topic'], 'body' => $data['body'],
            'owner_id' => $data['scope'] === 'me' ? $request->user()->id : null, 'updated_by' => $request->user()->id,
        ]);

        StaffAudit::log('support.reply.created', "Created the saved reply \"{$reply->title}\"", $reply, ['shared' => $reply->isShared()], $request->user()->id);

        return redirect()->route('admin.support.replies.index', ['reply' => $reply->id])->with(FlashAlertHelper::success('Reply saved'));
    }

    public function update(Request $request, int $reply)
    {
        $reply = SupportSavedReply::visibleTo($request->user())->findOrFail($reply);
        $data = $this->validated($request, creating: false);
        $reply->update(['title' => $data['title'], 'topic' => $data['topic'], 'body' => $data['body'], 'updated_by' => $request->user()->id]);

        StaffAudit::log('support.reply.updated', "Edited the saved reply \"{$reply->title}\"", $reply, ['shared' => $reply->isShared()], $request->user()->id);

        return redirect()->route('admin.support.replies.index', ['reply' => $reply->id])->with(FlashAlertHelper::success('Reply saved'));
    }

    public function destroy(Request $request, int $reply)
    {
        $reply = SupportSavedReply::visibleTo($request->user())->findOrFail($reply);
        $reply->delete();

        StaffAudit::log('support.reply.deleted', "Deleted the saved reply \"{$reply->title}\"", $reply, ['shared' => $reply->isShared()], $request->user()->id);

        return redirect()->route('admin.support.replies.index')->with(FlashAlertHelper::success('Reply deleted'));
    }

    /** @return array{title: string, topic: string, body: string, scope?: string} */
    private function validated(Request $request, bool $creating): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:120'],
            'topic' => ['required', Rule::in(SupportSavedReply::TOPICS)],
            'body' => ['required', 'string', 'min:10', 'max:'.TicketService::BODY_MAX, function (string $attribute, mixed $value, \Closure $fail) {
                $unknown = SavedReplies::unknown((string) $value);

                if ($unknown !== []) {
                    $fail('These placeholders do not exist: {'.implode('}, {', $unknown).'}. Use the buttons beside the box to add one.');
                }
            }],
            // Whether it is the team's or only yours is chosen once, when it is made
            'scope' => $creating ? ['required', Rule::in(['team', 'me'])] : ['prohibited'],
        ]);
    }
}
