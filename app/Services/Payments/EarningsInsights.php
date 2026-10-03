<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Models\JobEngagement;
use App\Models\LedgerTransaction;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\User;
use App\Support\Money;
use App\Support\Phone;
use Carbon\CarbonInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * What a member's Earnings page shows beyond their balances: how much they earned over a period and how that compares with the one before,
 * the same period bucketed for a chart and split between model sales and jobs, who earned the most, what is on its way, and a single feed of every
 * sale, job payment and withdrawal. Everything here is read from the sales, the ledger and the withdrawals; nothing is stored.
 */
class EarningsInsights
{
    /** Range key => [label, short label]. */
    public const RANGES = [
        '7d' => ['Last 7 days', '7D'],
        '30d' => ['Last 30 days', '30D'],
        '90d' => ['Last 90 days', '90D'],
        'ytd' => ['This year', 'YTD'],
        'all' => ['All time', 'All'],
    ];

    /** How many earners the "Where it comes from" panel lists. */
    public const TOP_EARNERS = 5;

    public const FEED_FILTERS = ['all' => 'All', 'sales' => 'Model sales', 'jobs' => 'Jobs', 'withdrawals' => 'Withdrawals'];

    public function __construct(protected EscrowService $escrow) {}

    public static function range(?string $key): string
    {
        return array_key_exists((string) $key, self::RANGES) ? $key : '30d';
    }

    public static function filter(?string $key): string
    {
        return array_key_exists((string) $key, self::FEED_FILTERS) ? $key : 'all';
    }

    /**
     * @return array{range: string, label: string, from: Carbon, to: Carbon, unit: string, earned: int, previous: ?int, change: ?float, count: int, average: int, buckets: list<array{label: string, models: int, jobs: int}>, by_source: array{models: int, jobs: int}, top: list<array{title: string, source: string, minor: int, count: int}>, max: int}
     */
    public function overview(User $user, string $range): array
    {
        $events = $this->events($user);
        [$from, $to] = $this->window($range, $events);
        $unit = $this->unit($from, $to);

        $inRange = $events->filter(fn ($e) => $e->at->betweenIncluded($from, $to));
        $earned = (int) $inRange->sum('minor');

        $previous = null;
        $change = null;

        if ($range !== 'all') {
            $length = max(1, (int) ceil($from->diffInDays($to)));
            $previousTo = $from->copy()->subSecond();
            $previousFrom = $from->copy()->subDays($length)->startOfDay();
            $previous = (int) $events->filter(fn ($e) => $e->at->betweenIncluded($previousFrom, $previousTo))->sum('minor');
            $change = $previous > 0 ? round(($earned - $previous) / $previous * 100, 1) : null;
        }

        $buckets = [];
        $cursor = $from->copy()->startOf($unit);

        while ($cursor->lte($to)) {
            $next = $cursor->copy()->add(1, $unit);
            $slice = $inRange->filter(fn ($e) => $e->at->gte($cursor) && $e->at->lt($next));
            $buckets[] = [
                'label' => match ($unit) {
                    'month' => $cursor->format($range === 'ytd' ? 'M' : 'M Y'), default => $cursor->format('M j')
                },
                'models' => (int) $slice->where('source', 'models')->sum('minor'),
                'jobs' => (int) $slice->where('source', 'jobs')->sum('minor'),
            ];
            $cursor = $next;
        }

        $top = $inRange->groupBy(fn ($e) => $e->source.'|'.$e->title)->map(fn (Collection $group) => [
            'title' => $group->first()->title, 'source' => $group->first()->source, 'minor' => (int) $group->sum('minor'), 'count' => $group->count(),
        ])->sortByDesc('minor')->take(self::TOP_EARNERS)->values()->all();

        return [
            'range' => $range, 'label' => self::RANGES[$range][0], 'from' => $from, 'to' => $to, 'unit' => $unit, 'earned' => $earned, 'previous' => $previous, 'change' => $change,
            'count' => $inRange->count(), 'average' => $inRange->count() > 0 ? intdiv($earned, $inRange->count()) : 0,
            'buckets' => $buckets, 'by_source' => ['models' => (int) $inRange->where('source', 'models')->sum('minor'), 'jobs' => (int) $inRange->where('source', 'jobs')->sum('minor')],
            'top' => $top, 'max' => max(1, ...array_map(fn ($b) => $b['models'] + $b['jobs'], $buckets ?: [['models' => 0, 'jobs' => 0]])),
        ];
    }

    /**
     * What is on its way to them, in minor units: model sales still in the hold (and when the first leaves it), and what clients have put into escrow for
     * jobs they are working on and have not been paid out yet.
     *
     * @return array{in_hold: int, next_release: ?Carbon, from_jobs: int, jobs_count: int}
     */
    public function incoming(User $user, int $inHold): array
    {
        $engagements = JobEngagement::forApplicant($user->id)->where('escrow_minor', '>', 0)->where('status', 'active')->get();
        $nextRelease = Payment::where('seller_id', $user->id)->where('purpose', Payment::PURPOSE_SALE)->where('status', PaymentStatus::Succeeded)->whereNull('released_at')->min('release_at');

        return [
            'in_hold' => $inHold,
            'next_release' => $nextRelease ? Carbon::parse($nextRelease) : null,
            'from_jobs' => (int) $engagements->sum(fn (JobEngagement $e) => $this->escrow->remainingNetMinor($e)),
            'jobs_count' => $engagements->filter(fn (JobEngagement $e) => $this->escrow->remainingNetMinor($e) > 0)->count(),
        ];
    }

    /**
     * Every sale, job payment and withdrawal, newest first, as rows a table can show. A sale that was refunded stays in the list, marked as such.
     *
     * @return LengthAwarePaginator<int, object>
     */
    public function feed(User $user, string $filter, int $page, int $perPage = 10): LengthAwarePaginator
    {
        $rows = collect();

        if (in_array($filter, ['all', 'sales'], true)) {
            Payment::with('product:id,slug,title,deleted_at')->where('seller_id', $user->id)->where('purpose', Payment::PURPOSE_SALE)->whereIn('status', [PaymentStatus::Succeeded, PaymentStatus::Refunded])
                ->latest('completed_at')->limit(200)->get()->each(function (Payment $sale) use ($rows) {
                    $refunded = $sale->status === PaymentStatus::Refunded;
                    $rows->push((object) [
                        'at' => $sale->completed_at ?? $sale->created_at, 'kind' => 'sale', 'title' => $sale->product?->title ?? __('A deleted model'),
                        'detail' => __(':tier licence · sold for :amount', ['tier' => $sale->tier?->label(), 'amount' => Money::formatMinor($sale->amount_minor, 0)]),
                        'status' => $refunded ? ['Refunded', 'neutral'] : ($sale->released_at ? ['Available', 'green'] : ['In hold until '.($sale->release_at?->format('M j') ?? ''), 'amber']),
                        'minor' => $refunded ? 0 : $sale->seller_share_minor, 'struck' => $refunded, 'url' => $sale->product && ! $sale->product->trashed() ? route('models.show', $sale->product) : null,
                    ]);
                });
        }

        if (in_array($filter, ['all', 'jobs'], true)) {
            foreach ($this->jobEvents($user) as $event) {
                $rows->push((object) [
                    'at' => $event->at, 'kind' => 'job', 'title' => $event->title, 'detail' => $event->detail, 'status' => ['Available', 'green'], 'minor' => $event->minor, 'struck' => false,
                    'url' => $event->engagement_id ? route('engagements.show', $event->engagement_id) : null,
                ]);
            }
        }

        if (in_array($filter, ['all', 'withdrawals'], true)) {
            Payout::where('user_id', $user->id)->latest('id')->limit(200)->get()->each(function (Payout $payout) use ($rows) {
                $rows->push((object) [
                    'at' => $payout->created_at, 'kind' => 'withdrawal', 'title' => __('Withdrawal to M-Pesa'), 'detail' => Phone::local($payout->msisdn).($payout->status === PayoutStatus::Paid ? ' · '.__('you received :amount', ['amount' => Money::formatMinor($payout->net_minor, 0)]) : ''),
                    'status' => [$payout->status->label(), $payout->status->tone()], 'minor' => -$payout->amount_minor, 'struck' => ! in_array($payout->status, [PayoutStatus::Requested, PayoutStatus::Processing, PayoutStatus::Paid], true),
                    'url' => null, 'payout' => $payout,
                ]);
            });
        }

        $rows = $rows->sortByDesc(fn ($row) => $row->at->timestamp)->values();

        return new LengthAwarePaginator($rows->forPage($page, $perPage)->values(), $rows->count(), $perPage, $page, ['path' => request()->url(), 'pageName' => 'activity']);
    }

    /** Everything that paid them, oldest first: sale shares (refunded sales are not earnings) and what jobs released. @return Collection<int, object{at: Carbon, minor: int, source: string, title: string}> */
    private function events(User $user): Collection
    {
        $sales = Payment::with('product:id,title')->where('seller_id', $user->id)->where('purpose', Payment::PURPOSE_SALE)->where('status', PaymentStatus::Succeeded)->get()
            ->map(fn (Payment $sale) => (object) ['at' => $sale->completed_at ?? $sale->created_at, 'minor' => $sale->seller_share_minor, 'source' => 'models', 'title' => $sale->product?->title ?? __('A deleted model')]);

        $jobs = $this->jobEvents($user)->map(fn ($event) => (object) ['at' => $event->at, 'minor' => $event->minor, 'source' => 'jobs', 'title' => $event->title]);

        return $sales->concat($jobs)->sortBy(fn ($e) => $e->at->timestamp)->values();
    }

    /** What jobs have released into their balance: each approval or settlement, with the job it was for. @return Collection<int, object{at: Carbon, minor: int, title: string, detail: string, engagement_id: ?int}> */
    private function jobEvents(User $user): Collection
    {
        $toTheirBalance = fn ($entries) => $entries->where('direction', 'credit')->whereHas('account', fn ($account) => $account->where('code', "user.{$user->id}.available"));

        $transactions = LedgerTransaction::whereIn('type', ['escrow_released', 'escrow_extra'])->whereHas('entries', $toTheirBalance)->with(['entries' => $toTheirBalance])->latest('id')->limit(500)->get();

        $ids = $transactions->map(fn (LedgerTransaction $t) => $t->meta['engagement_id'] ?? ($t->reference_type === JobEngagement::class ? $t->reference_id : null))->filter()->unique();
        $engagements = JobEngagement::with('application.job:id,title')->whereIn('id', $ids)->get()->keyBy('id');

        return $transactions->map(function (LedgerTransaction $t) use ($engagements) {
            $id = $t->meta['engagement_id'] ?? ($t->reference_type === JobEngagement::class ? $t->reference_id : null);

            return (object) [
                'at' => $t->occurred_at ?? $t->created_at, 'minor' => (int) $t->entries->sum('amount_minor'), 'title' => $engagements[$id]->application->job->title ?? __('A job'),
                'engagement_id' => $engagements->has($id) ? $id : null,
                'detail' => $t->type === 'escrow_extra' ? __('Settlement of a cancelled job') : (isset($t->meta['approved']) ? __(':approved of :total deliverables approved', ['approved' => $t->meta['approved'], 'total' => $t->meta['total']]) : __('Deliverable approved')),
            ];
        })->sortBy(fn ($e) => $e->at->timestamp)->values();
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function window(string $range, Collection $events): array
    {
        $to = now()->endOfDay();

        $from = match ($range) {
            '7d' => now()->subDays(6)->startOfDay(),
            '30d' => now()->subDays(29)->startOfDay(),
            '90d' => now()->subDays(89)->startOfDay(),
            'ytd' => now()->startOfYear(),
            default => ($events->first()?->at->copy() ?? now()->subDays(29))->startOfDay(),
        };

        return [$from, $to];
    }

    /** Day, week or month buckets, whichever keeps the chart to a readable number of bars. */
    private function unit(CarbonInterface $from, CarbonInterface $to): string
    {
        $days = $from->diffInDays($to);

        return $days <= 31 ? 'day' : ($days <= 120 ? 'week' : 'month');
    }
}
