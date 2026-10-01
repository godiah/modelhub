<?php

namespace App\Services\Marketplace;

use App\Enums\SellerStatus;
use App\Models\SellerProfile;
use App\Models\Staff;
use App\Models\User;
use App\Notifications\SellerApplicationSubmittedNotification;
use App\Notifications\SellerReviewedNotification;
use Illuminate\Support\Facades\DB;

/** Applying to sell 3D models, and a reviewer's decision on the application. */
class SellerOnboardingService
{
    /**
     * Submit (or resubmit after a rejection) a seller application. Returns why it cannot be submitted, or null.
     */
    public function apply(User $user, array $data): ?string
    {
        // Read from the database, not the user's cached relation, so two quick submissions cannot both create one.
        $existing = SellerProfile::where('user_id', $user->id)->first();

        if ($existing) {
            $blocker = match ($existing->status) {
                SellerStatus::Pending => 'Your application is already waiting for review.',
                SellerStatus::Approved => 'You are already approved to sell.',
                SellerStatus::Suspended => 'Your seller account is suspended, so you cannot send a new application.',
                SellerStatus::Rejected => null,
            };

            if ($blocker) {
                return $blocker;
            }
        }

        $fields = [
            'display_name' => $data['display_name'],
            'bio' => $data['bio'],
            'focus' => $data['focus'],
            'portfolio_url' => $data['portfolio_url'] ?? null,
            'status' => SellerStatus::Pending,
            'terms_accepted_at' => now(),
            'submitted_at' => now(),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_notes' => null,
        ];

        $profile = $existing ? tap($existing)->update($fields) : $user->sellerProfile()->create($fields);

        $profile->load('user');
        Staff::permission('review sellers')->where('is_active', true)->get()->each->notify(new SellerApplicationSubmittedNotification($profile));

        return null;
    }

    /**
     * Record a reviewer's decision. Returns why it is not allowed, or null once done.
     * approve: from pending, rejected or suspended (reinstating); reject: from pending; suspend: from approved.
     */
    public function review(SellerProfile $seller, Staff $reviewer, string $decision, ?string $notes): ?string
    {
        $outcome = null;

        $error = DB::transaction(function () use ($seller, $reviewer, $decision, $notes, &$outcome) {
            $current = SellerProfile::whereKey($seller->id)->lockForUpdate()->firstOrFail();

            $target = match ($decision) {
                'approve' => in_array($current->status, [SellerStatus::Pending, SellerStatus::Rejected, SellerStatus::Suspended], true) ? SellerStatus::Approved : null,
                'reject' => $current->status === SellerStatus::Pending ? SellerStatus::Rejected : null,
                'suspend' => $current->status === SellerStatus::Approved ? SellerStatus::Suspended : null,
                default => null,
            };

            if (! $target) {
                return "This seller is {$current->status->label()}, so that action is not available.";
            }

            if (in_array($target, [SellerStatus::Rejected, SellerStatus::Suspended], true) && blank($notes)) {
                return 'Give a reason: the seller will be shown it.';
            }

            $current->update([
                'status' => $target,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_notes' => $target === SellerStatus::Approved ? null : trim($notes),
            ]);
            $outcome = $current;

            return null;
        });

        if ($error) {
            return $error;
        }

        $outcome->load('user');
        $outcome->user->notify(new SellerReviewedNotification($outcome));

        return null;
    }

    /** Applications per status, for the review queue's filter pills. */
    public function counts(): array
    {
        $byStatus = SellerProfile::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return [
            'all' => (int) $byStatus->sum(),
            'pending' => (int) ($byStatus['pending'] ?? 0),
            'approved' => (int) ($byStatus['approved'] ?? 0),
            'rejected' => (int) ($byStatus['rejected'] ?? 0),
            'suspended' => (int) ($byStatus['suspended'] ?? 0),
        ];
    }
}
