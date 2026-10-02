<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Helpers\FlashAlertHelper;
use App\Http\Controllers\Controller;
use App\Models\LedgerTransaction;
use App\Models\LicenceDownload;
use App\Models\Payment;
use App\Services\Payments\RefundService;
use App\Support\Money;
use App\Support\Staff\ListSort;
use Illuminate\Http\Request;

/** Payments for models: all of them, those that need a look, and refunding. Permissions: view payments, refund payments. */
class AdminPaymentController extends Controller
{
    public const SORTS = ['date' => 'created_at', 'amount' => 'amount_minor', 'status' => 'status'];

    public const TABS = ['review' => 'Needs review', 'succeeded' => 'Paid', 'refunded' => 'Refunded', 'pending' => 'Waiting', 'failed' => 'Not paid', 'all' => 'All'];

    public function __construct(protected RefundService $refunds) {}

    public function index(Request $request)
    {
        $tab = array_key_exists($request->query('status'), self::TABS) ? $request->query('status') : 'all';
        $term = trim((string) $request->query('q'));
        [$sort, $dir] = ListSort::resolve($request, array_keys(self::SORTS), default: 'date', descFirst: ['date', 'amount']);

        $payments = Payment::with(['buyer:id,name,avatar', 'product:id,slug,title,deleted_at'])
            ->when($tab === 'failed', fn ($q) => $q->whereIn('status', [PaymentStatus::Failed, PaymentStatus::Cancelled, PaymentStatus::Expired]))
            ->when(! in_array($tab, ['all', 'failed'], true), fn ($q) => $q->where('status', $tab))
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w->where('reference', 'like', "%{$term}%")->orWhere('receipt', 'like', "%{$term}%")
                ->orWhereHas('buyer', fn ($b) => $b->where('name', 'like', "%{$term}%"))->orWhereHas('product', fn ($p) => $p->where('title', 'like', "%{$term}%"))))
            ->tap(fn ($q) => ListSort::apply($q, $sort, $dir, self::SORTS))
            ->paginate(15)->withQueryString();

        $by = Payment::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.payments.index', [
            'payments' => $payments, 'tab' => $tab, 'tabs' => self::TABS, 'term' => $term, 'sort' => $sort, 'dir' => $dir,
            'counts' => collect(self::TABS)->mapWithKeys(fn ($label, $key) => [$key => match ($key) {
                'all' => (int) $by->sum(),
                'failed' => (int) (($by['failed'] ?? 0) + ($by['cancelled'] ?? 0) + ($by['expired'] ?? 0)),
                default => (int) ($by[$key] ?? 0),
            }])->all(),
            'canRefund' => $request->user()->can('refund payments'),
        ]);
    }

    public function show(Request $request, Payment $payment)
    {
        $payment->load(['buyer:id,name,email,avatar', 'seller:id,name,avatar', 'product:id,slug,title,deleted_at', 'refunder:id,name', 'purchase.licence']);
        $licence = $payment->purchase?->licence;

        return view('admin.payments.show', [
            'payment' => $payment,
            'licence' => $licence,
            'downloads' => $licence ? LicenceDownload::where('issued_licence_id', $licence->id)->count() : 0,
            'postings' => LedgerTransaction::with('entries.account')->where('reference_type', $payment->getMorphClass())->where('reference_id', $payment->id)->orderBy('id')->get(),
            'canRefund' => $request->user()->can('refund payments'),
            'refundable' => $this->refunds->cannotRefund($payment) === null,
        ]);
    }

    public function refund(Request $request, Payment $payment)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:255']]);
        $result = $this->refunds->refund($payment, $request->user(), $data['reason']);

        if (is_string($result)) {
            return back()->with(FlashAlertHelper::error('Cannot refund this payment', $result));
        }

        return back()->with(FlashAlertHelper::success('Refund recorded', Money::formatMinor($result->received_minor ?? $result->amount_minor).' for '.$result->buyer->name.'. Now send the money back from the M-Pesa portal if you have not already.'));
    }
}
