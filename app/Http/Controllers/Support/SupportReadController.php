<?php

namespace App\Http\Controllers\Support;

use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Support\LicenceResource;
use App\Http\Resources\Support\PaymentResource;
use App\Http\Resources\Support\WithdrawalResource;
use App\Models\IssuedLicence;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\User;
use App\Services\Payments\PayoutEligibility;
use App\Services\Support\Inbound\SupportRequestRejected;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

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

    /**
     * The member's own payments (purchases and job funding), the ones that need attention first, at most three, with a count of all.
     * `?mpesa_code=` finds the payment with that M-Pesa receipt among THEIR payments; someone else's code finds nothing, like a made-up one.
     * Reads only: unlike the member's own payment page it never asks the gateway, so it can show a payment as still pending.
     */
    public function payments(Request $request): JsonResponse
    {
        $member = $this->member($request);
        $query = Payment::where('user_id', $member->getKey());

        $code = $request->query('mpesa_code');
        if ($code !== null) {
            $this->throttleCodeLookup($member);
            $code = is_string($code) && preg_match('/^[A-Za-z0-9]{10}$/', $code) ? strtoupper($code) : null;
            // A malformed code finds nothing, the same as a code that is not theirs
            $query = $code === null ? $query->whereRaw('1 = 0') : $query->where('receipt', $code);
        }

        $total = (clone $query)->count();
        $recent = $query->latest('id')->limit(30)->get();
        $states = $this->licenceStates($recent);

        $rank = fn (Payment $p): int => match (true) {
            $p->status === PaymentStatus::Review || ($p->isPending() && ! $p->hasExpired()) => 0,
            $p->status === PaymentStatus::Succeeded && ! $p->isEscrow() && ($states[$p->purchase_id] ?? 'none') === 'none' => 0,
            in_array($p->status, [PaymentStatus::Failed, PaymentStatus::Cancelled, PaymentStatus::Expired], true) || $p->hasExpired() => 1,
            default => 2,
        };

        $shown = $recent->sortBy(fn (Payment $p) => [$rank($p), -$p->id])->take(3)->values();

        return response()->json([
            'data' => $shown->map(fn (Payment $p) => (new PaymentResource($p))->withLicence($states[$p->purchase_id] ?? 'none')->resolve())->all(),
            'total' => $total,
            'as_of' => now()->toIso8601String(),
        ]);
    }

    /** One payment, by reference. Someone else's reference, a missing one and a malformed one are the same answer. */
    public function payment(Request $request, string $reference): JsonResponse
    {
        $payment = preg_match('/^MH[A-Z0-9]{10}$/', $reference)
            ? Payment::where('user_id', $this->member($request)->getKey())->where('reference', $reference)->first()
            : null;

        if ($payment === null) {
            return response()->json(['error' => 'not_found'], 404);
        }

        $states = $this->licenceStates(collect([$payment]));

        return response()->json(['data' => (new PaymentResource($payment))->withLicence($states[$payment->purchase_id] ?? 'none')->resolve(), 'as_of' => now()->toIso8601String()]);
    }

    /**
     * Whether each sale produced a licence that is still in force, worked out in one query for the payments about to be shown.
     *
     * @return array<int, string> purchase_id => 'active' | 'ended'
     */
    private function licenceStates($payments): array
    {
        $ids = $payments->where('purpose', Payment::PURPOSE_SALE)->pluck('purchase_id')->filter()->unique()->all();

        return IssuedLicence::whereIn('purchase_id', $ids)->get(['purchase_id', 'revoked_at'])
            ->groupBy('purchase_id')
            ->map(fn ($licences) => $licences->contains(fn (IssuedLicence $l) => $l->isActive()) ? 'active' : 'ended')
            ->all();
    }

    /** Looking payments up by M-Pesa code is the one thing worth guessing at, so it has its own, lower limit per member. */
    private function throttleCodeLookup(User $member): void
    {
        $key = 'support-reads:code:'.$member->getKey();

        if (RateLimiter::tooManyAttempts($key, (int) config('support.reads.code_lookups_per_minute'))) {
            throw new SupportRequestRejected('code_lookup_throttled', 429);
        }

        RateLimiter::hit($key, 60);
    }

    /**
     * Where the member stands on withdrawing: what is available and held, the most they could take out now, and what is stopping them. The rules come
     * from PayoutEligibility, the same ones a withdrawal request is checked against, so this can never say something the request would contradict.
     */
    public function balance(Request $request, PayoutEligibility $eligibility): JsonResponse
    {
        $summary = $eligibility->summary($this->member($request));

        // Each amount also as the words ModelHub shows, so the assistant copies it instead of formatting money itself
        foreach (['available', 'pending', 'min_withdrawal', 'fee', 'max_per_withdrawal', 'withdrawable', 'held'] as $name) {
            $summary[$name.'_display'] = Money::formatMinor($summary[$name.'_minor'], 0);
        }

        return response()->json(['data' => $summary, 'as_of' => now()->toIso8601String()]);
    }

    /** The licences the member holds, those in force first and the newest first within each, at most five, with a count of all. */
    public function licences(Request $request): JsonResponse
    {
        $member = $this->member($request);
        $query = IssuedLicence::where('user_id', $member->getKey());

        $shown = (clone $query)->with('product')->orderByRaw('revoked_at IS NOT NULL')->latest('issued_at')->latest('id')->limit(5)->get();

        return response()->json([
            'data' => LicenceResource::collection($shown)->resolve(),
            'total' => $query->count(),
            'active' => (clone $query)->whereNull('revoked_at')->count(),
            'as_of' => now()->toIso8601String(),
        ]);
    }
}
