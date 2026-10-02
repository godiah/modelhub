<?php

namespace App\Services\Payments;

use App\Enums\EngagementStatus;
use App\Enums\PaymentStatus;
use App\Models\EscrowRefund;
use App\Models\JobEngagement;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\User;
use App\Notifications\EscrowRefundedNotification;
use App\Notifications\JobEarningReleasedNotification;
use App\Services\Ledger\LedgerService;
use App\Support\Ledger\LedgerLine;
use App\Support\Money;
use App\Support\Phone;
use App\Support\Staff\StaffAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Job money held in escrow. The client pays the agreed amount once the freelancer has accepted (fund); it sits in a ledger account of its own for
 * that job, and leaves it as the client approves deliverables (releaseApproved): the freelancer's share to their available balance, the
 * platform's service fee to revenue. Whatever is still in escrow when a job ends early goes back to the client (see refundRemaining).
 *
 * The ledger is the record. job_engagements.escrow_minor / released_net_minor / released_fee_minor / refunded_minor are what the next release is
 * worked out from, and are only ever changed together with a ledger posting, inside one locked transaction.
 */
class EscrowService
{
    public function __construct(protected LedgerService $ledger) {}

    public function enabled(): bool
    {
        return (bool) config('marketplace.jobs_escrow_enabled');
    }

    /** The agreed amount, in minor units: what the client pays. */
    public function agreedMinor(JobEngagement $engagement): int
    {
        return (int) round((float) $engagement->agreed_amount * 100);
    }

    /** The platform's service fee, in minor units: taken from the freelancer's side as releases happen. */
    public function feeMinor(JobEngagement $engagement): int
    {
        return min($this->agreedMinor($engagement), (int) round((float) $engagement->service_fee * 100));
    }

    /** What the freelancer gets in all: the agreed amount less the fee. */
    public function netMinor(JobEngagement $engagement): int
    {
        return $this->agreedMinor($engagement) - $this->feeMinor($engagement);
    }

    /** Money still held for this job. */
    public function remainingMinor(JobEngagement $engagement): int
    {
        return $this->ledger->escrowAccount($engagement)->balanceMinor();
    }

    /** Why this client cannot pay into this engagement's escrow now, or null if they can. */
    public function cannotFund(JobEngagement $engagement, User $client): ?string
    {
        if (! $this->enabled()) {
            return 'Paying for jobs through the platform is not switched on yet.';
        }

        if ($engagement->application->poster_id !== $client->id) {
            return 'Only the client who hired the freelancer can fund this job.';
        }

        if ($engagement->escrow_minor > 0) {
            return 'This job has already been funded.';
        }

        if ($engagement->status !== EngagementStatus::ApplicantAccepted) {
            return 'This job is not waiting for funding.';
        }

        $agreed = $this->agreedMinor($engagement);

        if ($agreed <= 0 || $agreed % 100 !== 0) {
            return 'The agreed amount is not a whole number of shillings, so it cannot be paid by M-Pesa. Ask the freelancer to change the offer.';
        }

        if ($agreed / 100 > config('payments.max_kes')) {
            return 'M-Pesa takes up to KES '.number_format(config('payments.max_kes')).' in one payment, and this job is more than that.';
        }

        return null;
    }

    /**
     * Money for the job has arrived: hold it in the job's escrow and open the job for work, all inside PaymentService's transaction. Returns the
     * reason it cannot be used (the job was cancelled in the meantime, or was already funded), in which case nothing is changed and the caller parks the money.
     */
    public function fund(Payment $payment): ?string
    {
        $engagement = JobEngagement::with('application.job')->whereKey($payment->engagement_id)->lockForUpdate()->first();

        if (! $engagement) {
            return 'The job this payment was for no longer exists.';
        }

        if ($engagement->escrow_minor > 0) {
            return 'This job had already been funded.';
        }

        if ($engagement->status !== EngagementStatus::ApplicantAccepted) {
            return 'The job was no longer waiting for funding ('.strtolower($engagement->status->label()).').';
        }

        $this->ledger->post('escrow_funded', "escrow:fund:payment:{$payment->id}", [
            LedgerLine::debit($this->ledger->platformAccount('gateway'), $payment->amount_minor),
            LedgerLine::credit($this->ledger->escrowAccount($engagement), $payment->amount_minor),
        ], 'Escrow funded for "'.$engagement->application->job->title.'"', $payment, ['receipt' => $payment->receipt, 'engagement_id' => $engagement->id]);

        $engagement->update(['escrow_minor' => $payment->amount_minor, 'payment_escrowed_at' => now(), 'status' => EngagementStatus::Active, 'started_at' => now()]);

        return null;
    }

    /**
     * Release what the approved deliverables have earned. The amount released so far is brought up to net x approved / total (and the fee likewise),
     * so it never matters in what order deliverables are approved or how many there are: adding one later lowers the target but never takes back
     * what was released, and with every deliverable approved exactly the net amount and the fee have moved. A job that was never funded releases nothing.
     * Returns what reached the freelancer this time, in minor units. Safe to call any number of times.
     */
    public function releaseApproved(JobEngagement $engagement): int
    {
        $released = 0;
        $freelancer = null;
        $title = '';

        DB::transaction(function () use ($engagement, &$released, &$freelancer, &$title) {
            $locked = JobEngagement::with('application.applicant', 'application.job')->whereKey($engagement->id)->lockForUpdate()->firstOrFail();

            if ($locked->escrow_minor <= 0) {
                return;
            }

            $total = $locked->deliverables()->count();
            $approved = $locked->deliverables()->where('status', 'approved')->count();

            if ($total === 0 || $approved === 0) {
                return;
            }

            $fee = $this->feeMinor($locked);
            $net = $locked->escrow_minor - $fee;
            $targetNet = $approved >= $total ? $net : (int) round($net * $approved / $total);
            $targetFee = $approved >= $total ? $fee : (int) round($fee * $approved / $total);

            $netNow = max(0, $targetNet - $locked->released_net_minor);
            $feeNow = max(0, $targetFee - $locked->released_fee_minor);

            if ($netNow + $feeNow <= 0) {
                return;
            }

            $freelancer = $locked->application->applicant;
            $title = $locked->application->job->title;
            $newNet = $locked->released_net_minor + $netNow;
            $newFee = $locked->released_fee_minor + $feeNow;

            $lines = [LedgerLine::debit($this->ledger->escrowAccount($locked), $netNow + $feeNow)];

            if ($netNow > 0) {
                $lines[] = LedgerLine::credit($this->ledger->userAccount($freelancer, 'available'), $netNow);
            }

            if ($feeNow > 0) {
                $lines[] = LedgerLine::credit($this->ledger->platformAccount('revenue'), $feeNow);
            }

            $this->ledger->post('escrow_released', "escrow:release:{$locked->id}:{$newNet}:{$newFee}", $lines, "Released for \"{$title}\" ({$approved} of {$total} deliverables approved)", $locked, ['approved' => $approved, 'total' => $total, 'engagement_id' => $locked->id]);

            $locked->update(['released_net_minor' => $newNet, 'released_fee_minor' => $newFee]);

            // Everything has left escrow: the job is paid out in full
            if ($newNet + $newFee >= $locked->escrow_minor - $locked->refunded_minor && $locked->payment_released_at === null) {
                $locked->update(['payment_released_at' => now()]);
            }

            $released = $netNow;
        });

        if ($released > 0 && $freelancer) {
            $freelancer->notify(new JobEarningReleasedNotification($engagement, $title, $released));
        }

        return $released;
    }

    /** What the freelancer can still be paid for this job (net), in minor units. */
    public function remainingNetMinor(JobEngagement $engagement): int
    {
        return max(0, $this->netMinor($engagement) - $engagement->released_net_minor);
    }

    /**
     * Where a job's money stands, for pages that show it. state: none (never funded), held (in escrow, nothing due back), waiting (the client's
     * review window is open), due (the client's to have back, staff to record it), closed (nothing left in escrow).
     *
     * @return array{funded: bool, amount: int, released: int, released_net: int, released_fee: int, refunded: int, remaining: int, remaining_net: int, refund_due_at: ?Carbon, state: string}
     */
    public function summary(JobEngagement $engagement): array
    {
        $remaining = $engagement->escrowRemainingMinor();
        $dueAt = $engagement->escrow_refund_due_at;

        return [
            'funded' => $engagement->escrow_minor > 0, 'amount' => $engagement->escrow_minor, 'released' => $engagement->released_net_minor + $engagement->released_fee_minor,
            'released_net' => $engagement->released_net_minor, 'released_fee' => $engagement->released_fee_minor, 'refunded' => $engagement->refunded_minor,
            'remaining' => $remaining, 'remaining_net' => $engagement->escrow_minor > 0 ? $this->remainingNetMinor($engagement) : 0, 'refund_due_at' => $dueAt,
            'state' => match (true) {
                $engagement->escrow_minor <= 0 => 'none',
                $remaining <= 0 => 'closed',
                $dueAt === null => 'held',
                $dueAt->isFuture() => 'waiting',
                default => 'due',
            },
        ];
    }

    /**
     * The engagement was cancelled. What is left in escrow is the client's to have back: at once if the freelancer walked away, otherwise after the
     * review window in which the client can still pay for work that was done. (A partial payment freezes it again until it is settled.)
     */
    public function onCancelled(JobEngagement $engagement, string $cancellationType): void
    {
        if ($engagement->escrow_minor <= 0 || $engagement->escrowRemainingMinor() <= 0) {
            return;
        }

        $engagement->update(['escrow_refund_due_at' => $cancellationType === 'freelancer_initiated' ? now() : now()->addDays((int) config('payments.escrow_cancel_window_days'))]);
    }

    /** A partial payment is open: what is left stays in escrow until it is accepted or a dispute over it is resolved. */
    public function freeze(JobEngagement $engagement): void
    {
        if ($engagement->escrow_minor > 0) {
            $engagement->update(['escrow_refund_due_at' => null]);
        }
    }

    /** Can the client still open a partial payment: no, once what is left in escrow has become the client's to have back. */
    public function reviewWindowOpen(JobEngagement $engagement): bool
    {
        return $engagement->escrow_refund_due_at === null || $engagement->escrow_refund_due_at->isFuture();
    }

    /** The engagement is settled: whatever is left in escrow is now the client's to have back. */
    public function closeOut(JobEngagement $engagement): void
    {
        if ($engagement->escrow_minor > 0 && $engagement->fresh()->escrowRemainingMinor() > 0) {
            $engagement->update(['escrow_refund_due_at' => now()]);
        }
    }

    /**
     * Pay the freelancer more than approval has released, out of what is left in escrow: a partial payment the freelancer accepted, or the amount staff
     * decided on in a dispute. $netMinor is what the freelancer receives; the service fee is taken in the same proportion as for a release. Capped at
     * what is left. Returns what reached the freelancer. $key makes it happen once however often it is called.
     */
    public function payExtra(JobEngagement $engagement, int $netMinor, string $key, string $description, ?Model $reference = null, ?Staff $by = null): int
    {
        $paid = 0;
        $freelancer = null;
        $title = '';

        DB::transaction(function () use ($engagement, $netMinor, $key, $description, $reference, $by, &$paid, &$freelancer, &$title) {
            $locked = JobEngagement::with('application.applicant', 'application.job')->whereKey($engagement->id)->lockForUpdate()->firstOrFail();

            if ($locked->escrow_minor <= 0) {
                return;
            }

            $fee = $this->feeMinor($locked);
            $net = $locked->escrow_minor - $fee;
            $payNet = min(max(0, $netMinor), max(0, $net - $locked->released_net_minor));
            $payFee = $net > 0 ? min((int) round($payNet * $fee / $net), max(0, $fee - $locked->released_fee_minor)) : 0;
            $payNet = min($payNet, $locked->escrowRemainingMinor() - $payFee);

            if ($payNet <= 0) {
                return;
            }

            $freelancer = $locked->application->applicant;
            $title = $locked->application->job->title;

            $lines = [LedgerLine::debit($this->ledger->escrowAccount($locked), $payNet + $payFee), LedgerLine::credit($this->ledger->userAccount($freelancer, 'available'), $payNet)];

            if ($payFee > 0) {
                $lines[] = LedgerLine::credit($this->ledger->platformAccount('revenue'), $payFee);
            }

            $this->ledger->post('escrow_extra', "escrow:extra:{$key}", $lines, $description, $reference ?? $locked, ['engagement_id' => $locked->id], $by);
            $locked->update(['released_net_minor' => $locked->released_net_minor + $payNet, 'released_fee_minor' => $locked->released_fee_minor + $payFee]);

            $paid = $payNet;
        });

        if ($paid > 0 && $freelancer) {
            $freelancer->notify(new JobEarningReleasedNotification($engagement, $title, $paid));
        }

        return $paid;
    }

    /**
     * Staff record that what is left in a job's escrow goes back to the client (who then gets it by M-Pesa, sent by staff). The books are posted now:
     * escrow to the gateway, as for a refunded sale. Only once the money is the client's to have back. Returns the refund, or why it cannot be.
     */
    public function refundRemaining(JobEngagement $engagement, Staff $by, ?string $note = null): EscrowRefund|string
    {
        $result = DB::transaction(function () use ($engagement, $by, $note) {
            $locked = JobEngagement::with('application.job', 'application.poster')->whereKey($engagement->id)->lockForUpdate()->firstOrFail();
            $remaining = $locked->escrowRemainingMinor();

            if ($locked->escrow_minor <= 0 || $remaining <= 0) {
                return 'Nothing is left in escrow for this job.';
            }

            if ($locked->escrow_refund_due_at === null || $locked->escrow_refund_due_at->isFuture()) {
                return $locked->escrow_refund_due_at === null
                    ? 'A payment or dispute for this job is still open, so its escrow stays where it is.'
                    : 'The client\'s review window is still open until '.$locked->escrow_refund_due_at->format('M j, Y').'.';
            }

            $funding = Payment::where('engagement_id', $locked->id)->where('purpose', Payment::PURPOSE_ESCROW)->where('status', PaymentStatus::Succeeded)->latest('id')->first();
            $title = $locked->application->job->title;
            $after = $locked->refunded_minor + $remaining;

            $this->ledger->post('escrow_refund', "escrow:refund:{$locked->id}:{$after}", [
                LedgerLine::debit($this->ledger->escrowAccount($locked), $remaining),
                LedgerLine::credit($this->ledger->platformAccount('gateway'), $remaining),
            ], "Escrow for \"{$title}\" returned to the client", $locked, ['engagement_id' => $locked->id], $by);

            $locked->update(['refunded_minor' => $after, 'escrow_refund_due_at' => null]);

            $refund = EscrowRefund::create([
                'engagement_id' => $locked->id, 'user_id' => $locked->application->poster_id, 'amount_minor' => $remaining, 'currency' => config('marketplace.currency'),
                'msisdn' => $funding?->msisdn, 'funding_receipt' => $funding?->receipt, 'staff_id' => $by->id, 'note' => $note ? trim($note) : null,
            ]);

            StaffAudit::log('escrow.refunded', 'Recorded the return of '.Money::formatMinor($remaining)." from the escrow of \"{$title}\" to {$locked->application->poster->name}", $locked, ['note' => $note], $by->id);

            return $refund;
        });

        if ($result instanceof EscrowRefund) {
            $engagement->loadMissing('application.job', 'application.poster');
            $engagement->application->poster->notify(new EscrowRefundedNotification($engagement, $engagement->application->job->title, $result->amount_minor, $result->msisdn ? Phone::local($result->msisdn) : null));
        }

        return $result;
    }

    /** What reaches the client as a receipt, for the notification text. */
    public static function describe(int $minor): string
    {
        return Money::formatMinor($minor);
    }
}
