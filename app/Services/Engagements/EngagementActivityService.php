<?php

namespace App\Services\Engagements;

use App\Models\JobEngagement;
use App\Support\Money;
use Illuminate\Support\Carbon;

/**
 * The engagement's Activity tab: one newest-first feed assembled from the engagement's own dates, its
 * deliverables and any cancellation, payment and dispute records. Requires deliverables, cancellation
 * (with initiator and dispute) and partialPayments to be eager-loaded.
 */
class EngagementActivityService
{
    /**
     * @return list<array{at: Carbon, tone: string, icon: string, title: string, detail: string|null}>
     */
    public function timeline(JobEngagement $engagement): array
    {
        $events = [];

        $add = function (?Carbon $at, string $tone, string $icon, string $title, ?string $detail = null) use (&$events) {
            if ($at !== null) {
                $events[] = ['at' => $at, 'tone' => $tone, 'icon' => $icon, 'title' => $title, 'detail' => $detail];
            }
        };

        $add($engagement->employer_accepted_at, 'neutral', 'paper-airplane', __('Offer sent'), __('The client selected the freelancer and made an offer.'));
        $add($engagement->started_at, 'teal', 'check-circle', __('Work started'), __('The freelancer accepted the offer.'));
        $add($engagement->payment_escrowed_at, 'teal', 'banknotes', __('Payment escrowed'), Money::format($engagement->agreed_amount).' '.__('is held until the work is approved.'));

        foreach ($engagement->deliverables as $deliverable) {
            $add($deliverable->created_at, 'neutral', 'clipboard-list', __('Deliverable added: :title', ['title' => $deliverable->title]));
            $add($deliverable->submitted_at, 'blue', 'cloud-arrow-up', __('Submitted: :title', ['title' => $deliverable->title]), $deliverable->submission_notes);
            $add($deliverable->approved_at, 'teal', 'check-circle', __('Approved: :title', ['title' => $deliverable->title]), $deliverable->feedback);
            $add($deliverable->rejected_at, 'red', 'exclamation-triangle', __('Changes requested: :title', ['title' => $deliverable->title]), $deliverable->feedback);
        }

        $add($engagement->completed_at, 'teal', 'check-circle', __('Engagement completed'));
        $add($engagement->payment_released_at, 'teal', 'banknotes', __('Payment released'), Money::format($engagement->net_amount));

        if ($cancellation = $engagement->cancellation) {
            $reason = $cancellation->reason_label;
            $add($engagement->cancelled_at ?? $cancellation->created_at, 'red', 'x-circle-solid',
                __('Engagement cancelled'),
                trim(($cancellation->initiator?->name ? __('By :name', ['name' => $cancellation->initiator->name]).' · ' : '').$reason.($cancellation->reason_details ? ': '.$cancellation->reason_details : '')));
            $add($cancellation->freelancer_accepted_at, 'blue', 'check-circle', __('Payment accepted by the freelancer'));

            if ($dispute = $cancellation->dispute) {
                $add($dispute->created_at, 'red', 'shield-check', __('Payment disputed'), $dispute->formatted_reason.($dispute->dispute_details ? ': '.$dispute->dispute_details : ''));
                $add($dispute->resolved_at, 'teal', 'shield-check', __('Dispute resolved'), $dispute->resolution_amount !== null ? __('Resolution amount: :amount', ['amount' => Money::format($dispute->resolution_amount)]) : $dispute->resolution_notes);
            }
        }

        foreach ($engagement->partialPayments as $payment) {
            $add($payment->processed_at, 'blue', 'banknotes', __('Partial payment processed'), Money::format($payment->amount).($payment->notes ? ' · '.$payment->notes : ''));
            $add($payment->accepted_at, 'teal', 'check-circle', __('Partial payment accepted'), Money::format($payment->amount));
            $add($payment->finalized_at, 'teal', 'check-circle', __('Payment finalised'), $payment->final_amount !== null ? Money::format($payment->final_amount) : null);
        }

        usort($events, fn (array $a, array $b) => $b['at'] <=> $a['at']);

        return $events;
    }
}
