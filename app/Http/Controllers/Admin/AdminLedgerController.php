<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LedgerTransaction;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\User;
use App\Services\Ledger\LedgerReport;
use Illuminate\Http\Request;

/** The ledger, read-only: where the money sits, whether it adds up, and every posting. Permission: view ledger. */
class AdminLedgerController extends Controller
{
    /** Tab => the transaction types it shows. */
    public const TABS = [
        'all' => ['All', []],
        'sales' => ['Sales', ['sale', 'unallocated']],
        'releases' => ['Releases', ['release']],
        'payouts' => ['Withdrawals', ['payout_requested', 'payout_paid', 'payout_returned']],
        'refunds' => ['Refunds', ['refund', 'refund_unallocated']],
    ];

    public function index(Request $request, LedgerReport $report)
    {
        $tab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'all';
        $term = trim((string) $request->query('q'));
        $member = $request->integer('member') ? User::find($request->integer('member'), ['id', 'name']) : null;

        $transactions = LedgerTransaction::with('entries.account.user:id,name')->withSum(['entries as debit_total' => fn ($q) => $q->where('direction', 'debit')], 'amount_minor')
            ->when(self::TABS[$tab][1] !== [], fn ($q) => $q->whereIn('type', self::TABS[$tab][1]))
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w->where('description', 'like', "%{$term}%")->orWhere('idempotency_key', 'like', "%{$term}%")))
            ->when($member, fn ($q) => $q->whereHas('entries.account', fn ($a) => $a->where('user_id', $member->id)))
            ->latest('id')->paginate(20)->withQueryString();

        return view('admin.ledger.index', ['summary' => $report->summary(), 'transactions' => $transactions, 'tab' => $tab, 'tabs' => self::TABS, 'term' => $term, 'member' => $member]);
    }

    public function show(LedgerTransaction $transaction)
    {
        $transaction->load(['entries.account.user:id,name', 'staff:id,name']);

        return view('admin.ledger.show', ['transaction' => $transaction, 'reference' => $transaction->reference_type ? $this->referenceLink($transaction) : null]);
    }

    /** Where the thing a posting was about can be opened, when staff have a page for it. @return array{label: string, url: string}|null */
    private function referenceLink(LedgerTransaction $transaction): ?array
    {
        if ($transaction->reference_type === Payment::class && ($reference = Payment::whereKey($transaction->reference_id)->value('reference'))) {
            return ['label' => 'Payment '.$reference, 'url' => route('admin.payments.show', $reference)];
        }

        if ($transaction->reference_type === Payout::class && ($reference = Payout::whereKey($transaction->reference_id)->value('reference'))) {
            return ['label' => 'Withdrawal '.$reference, 'url' => route('admin.payouts.index', ['status' => 'all'])];
        }

        return null;
    }
}
