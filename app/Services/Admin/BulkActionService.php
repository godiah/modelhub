<?php

namespace App\Services\Admin;

use App\Models\JobPaymentDispute;
use App\Models\ModelJob;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\SellerProfile;
use App\Models\Staff;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Marketplace\ProductRatingService;
use App\Services\Marketplace\ProductReviewService;
use App\Services\Marketplace\SellerOnboardingService;
use App\Services\Support\Tickets\TicketService;
use App\Support\Staff\BulkActions;
use App\Support\Staff\StaffAudit;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Runs one bulk action over a list of selected items. Every item goes through the same service call, and writes the same audit entry,
 * as the one-at-a-time action, so a rule (a seller who is not pending, the last Super admin) holds here too. An item that cannot
 * take the action is skipped with the reason, never half-done, and one failure never stops the rest.
 */
class BulkActionService
{
    public function __construct(
        protected ProductReviewService $products,
        protected SellerOnboardingService $sellers,
        protected ProductRatingService $ratings,
        protected MemberManagementService $members,
        protected ProjectModerationService $projects,
        protected StaffManagementService $staff,
        protected TicketService $tickets,
    ) {}

    /**
     * @param  list<int|string>  $ids
     * @return array{action: string, label: string, past: string, noun: string, done: list<string>, skipped: list<array{name: string, why: string}>}
     */
    public function run(Staff $actor, string $action, array $ids, ?string $reason = null): array
    {
        $definition = BulkActions::get($action) ?? throw new \InvalidArgumentException("Unknown bulk action [{$action}].");
        $ids = array_values(array_unique($ids));
        $result = ['action' => $action, 'label' => $definition['label'], 'past' => $definition['past'], 'noun' => $definition['noun'], 'done' => [], 'skipped' => []];

        if ($action === 'notification.read') {
            $result['done'] = array_fill(0, $actor->unreadNotifications()->whereIn('id', $ids)->update(['read_at' => now()]), 'notification');

            return $result;
        }

        $items = $definition['model']::query()->with($definition['with'])->whereKey($ids)->get()->keyBy(fn (Model $item) => $item->getKey());

        foreach ($ids as $id) {
            $item = $items[$id] ?? null;

            if (! $item) {
                $result['skipped'][] = ['name' => "#{$id}", 'why' => 'It no longer exists.'];

                continue;
            }

            try {
                $error = $this->apply($action, $item, $actor, $reason);
            } catch (Throwable $e) {
                report($e);
                $error = 'Something went wrong, so it was left as it was.';
            }

            $error === null ? $result['done'][] = $this->name($item) : $result['skipped'][] = ['name' => $this->name($item), 'why' => $error];
        }

        if ($result['done'] !== [] || $result['skipped'] !== []) {
            StaffAudit::log('bulk.'.$action, sprintf('Bulk "%s": %d done, %d skipped', $definition['label'], count($result['done']), count($result['skipped'])), details: array_filter(['reason' => $reason, 'done' => $result['done'], 'skipped' => $result['skipped']]), staffId: $actor->id);
        }

        return $result;
    }

    /** Null when it worked, otherwise why it could not be done. */
    private function apply(string $action, Model $item, Staff $actor, ?string $reason): ?string
    {
        return match ($action) {
            'model.publish' => $this->publishModel($item, $actor),
            'model.reject' => $this->sendBackModel($item, $actor, $reason),
            'seller.approve' => $this->reviewSeller($item, $actor, 'approve', null),
            'seller.reject' => $this->reviewSeller($item, $actor, 'reject', $reason),
            'review.dismiss' => $this->dismissReports($item, $actor),
            'review.hide' => $this->hideReview($item, $actor, $reason),
            'dispute.assign' => $this->assignDispute($item, $actor),
            'support.assign' => $this->assignTicket($item, $actor),
            'member.suspend' => $this->members->suspend($item, $actor, $reason),
            'member.reinstate' => $this->members->reinstate($item, $actor),
            'project.takedown' => $this->projects->takeDown($item, $actor, $reason),
            'project.restore' => $this->projects->restore($item, $actor),
            'staff.deactivate' => $this->staff->deactivate($item, $actor),
            'staff.reactivate' => $this->reactivate($item),
        };
    }

    private function publishModel(Product $product, Staff $actor): ?string
    {
        if ($error = $this->products->review($product, $actor, 'publish', null)) {
            return $error;
        }

        StaffAudit::log('model.published', "Published \"{$product->title}\"", $product, staffId: $actor->id);

        return null;
    }

    private function sendBackModel(Product $product, Staff $actor, ?string $reason): ?string
    {
        if ($error = $this->products->review($product, $actor, 'reject', $reason)) {
            return $error;
        }

        StaffAudit::log('model.sent-back', "Asked for changes to \"{$product->title}\"", $product, ['notes' => $reason], $actor->id);

        return null;
    }

    private function reviewSeller(SellerProfile $seller, Staff $actor, string $decision, ?string $reason): ?string
    {
        if ($error = $this->sellers->review($seller, $actor, $decision, $reason)) {
            return $error;
        }

        StaffAudit::log('seller.'.($decision === 'approve' ? 'approved' : 'rejected'), $decision === 'approve' ? "Approved {$seller->display_name}" : "Rejected the application of {$seller->display_name}", $seller, array_filter(['notes' => $reason]), $actor->id);

        return null;
    }

    private function dismissReports(ProductReview $review, Staff $actor): ?string
    {
        if (! $review->reports()->where('status', 'open')->exists()) {
            return 'It has no open reports.';
        }

        $this->ratings->dismissReports($review, $actor);
        StaffAudit::log('review.reports-dismissed', 'Dismissed the reports on a review of "'.$review->product->title.'"', $review, staffId: $actor->id);

        return null;
    }

    private function hideReview(ProductReview $review, Staff $actor, ?string $reason): ?string
    {
        if ($error = $this->ratings->hide($review, $actor, (string) $reason)) {
            return $error;
        }

        StaffAudit::log('review.hidden', 'Hid a review of "'.$review->product->title.'"', $review, ['reason' => $reason], $actor->id);

        return null;
    }

    /** Disputes someone else is already handling are left to them: a bulk click must not take work off a colleague. */
    private function assignDispute(JobPaymentDispute $dispute, Staff $actor): ?string
    {
        if ($dispute->isResolved()) {
            return 'It has already been resolved.';
        }

        if ($dispute->admin_assigned && $dispute->admin_assigned !== $actor->id) {
            return ($dispute->assignedAdmin?->name ?? 'A colleague').' is already handling it.';
        }

        if ($dispute->admin_assigned === $actor->id) {
            return 'You are already handling it.';
        }

        $dispute->assignAdmin($actor->id);
        StaffAudit::log('dispute.assigned', 'Took on dispute #'.$dispute->id, $dispute, staffId: $actor->id);

        return null;
    }

    /** Like disputes: a request a colleague is already handling is left to them, and one that is finished is not taken on. */
    private function assignTicket(SupportTicket $ticket, Staff $actor): ?string
    {
        if (! $ticket->status->isActive()) {
            return 'It is already finished.';
        }

        if ($ticket->assignee_id === $actor->id) {
            return 'You are already handling it.';
        }

        if ($ticket->assignee_id !== null) {
            return ($ticket->assignee?->name ?? 'A colleague').' is already handling it.';
        }

        $this->tickets->assign($ticket, $actor, $actor);

        return null;
    }

    private function reactivate(Staff $account): ?string
    {
        if ($account->is_active) {
            return 'It is already active.';
        }

        $this->staff->reactivate($account);

        return null;
    }

    private function name(Model $item): string
    {
        return match (true) {
            $item instanceof Product, $item instanceof ModelJob => $item->title,
            $item instanceof SellerProfile => $item->display_name,
            $item instanceof ProductReview => 'Review of "'.($item->product?->title ?? 'a model').'"',
            $item instanceof JobPaymentDispute => 'Dispute #'.$item->id.' · '.($item->cancellation?->engagement?->application?->job?->title ?? 'a project'),
            $item instanceof SupportTicket => $item->reference,
            $item instanceof User, $item instanceof Staff => $item->name,
            default => '#'.$item->getKey(),
        };
    }
}
