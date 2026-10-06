<?php

namespace App\Http\Controllers\Support;

use App\Enums\PayoutStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Support\WithdrawalResource;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The read API for the support assistant. Everything here is GET, about the member named by the signed claim, and writes nothing. */
class SupportReadController extends Controller
{
    /** Proves the plumbing end to end without reading any record: the call was signed, the claim names an active member in stage. */
    public function ping(Request $request): JsonResponse
    {
        return response()->json(['status' => 'ok', 'as_of' => now()->toIso8601String()]);
    }

    /**
     * The member's withdrawals, the ones that need attention first: still open, then failed or turned down, then the rest, newest first
     * within each. At most three are returned with a count of all of them, so a member with many is told so and pointed at the full page.
     */
    public function withdrawals(Request $request): JsonResponse
    {
        $member = $this->member($request);

        $recent = Payout::where('user_id', $member->getKey())->latest('id')->limit(30)->get();

        $rank = fn (Payout $p): int => match (true) {
            $p->status->isOpen() => 0,
            in_array($p->status, [PayoutStatus::Failed, PayoutStatus::Rejected], true) => 1,
            default => 2,
        };

        $shown = $recent->sortBy(fn (Payout $p) => [$rank($p), -$p->id])->take(3)->values();

        return response()->json([
            'data' => WithdrawalResource::collection($shown)->resolve(),
            'total' => Payout::where('user_id', $member->getKey())->count(),
            'as_of' => now()->toIso8601String(),
        ]);
    }

    /** One withdrawal, by reference. Someone else's reference and one that does not exist are the same answer. */
    public function withdrawal(Request $request, string $reference): JsonResponse
    {
        // A malformed reference gets the same answer as a missing one, so the format is not something to probe
        $payout = preg_match('/^PO[A-Z0-9]{10}$/', $reference)
            ? Payout::where('user_id', $this->member($request)->getKey())->where('reference', $reference)->first()
            : null;

        if ($payout === null) {
            return response()->json(['error' => 'not_found'], 404);
        }

        return response()->json(['data' => (new WithdrawalResource($payout))->resolve(), 'as_of' => now()->toIso8601String()]);
    }

    /** The member named by the signed claim; the middleware has already checked it. */
    private function member(Request $request): User
    {
        return $request->attributes->get('support.member');
    }
}
