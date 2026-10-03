<?php

namespace App\Http\Controllers;

use App\Enums\PayoutStatus;
use App\Helpers\FlashAlertHelper;
use App\Models\Payout;
use App\Services\Payments\EarningsInsights;
use App\Services\Payments\EarningsService;
use App\Services\Payments\PayoutService;
use App\Support\Money;
use App\Support\Phone;
use Illuminate\Http\Request;

/** A member's earnings: what they have made, what is held and what they can withdraw, their sales and withdrawals, and asking for a withdrawal. */
class EarningsController extends Controller
{
    public function __construct(protected EarningsService $earnings, protected PayoutService $payouts) {}

    public function index(Request $request, EarningsInsights $insights)
    {
        $user = $request->user();
        $range = EarningsInsights::range($request->query('range'));
        $filter = EarningsInsights::filter($request->query('show'));
        $summary = $this->earnings->summary($user);

        return view('earnings.index', [
            'summary' => $summary,
            'range' => $range,
            'filter' => $filter,
            'overview' => $insights->overview($user, $range),
            'incoming' => $insights->incoming($user, $summary['pending']),
            'feed' => $insights->feed($user, $filter, max(1, $request->integer('activity', 1))),
            'open' => Payout::where('user_id', $user->id)->whereIn('status', [PayoutStatus::Requested, PayoutStatus::Processing])->latest('id')->first(),
        ]);
    }

    public function withdraw(Request $request)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'phone' => ['required', 'string', 'max:20', fn ($attribute, $value, $fail) => Phone::msisdn($value) ?: $fail('Enter a valid Safaricom number, for example 0712 345 678.')],
        ]);

        $result = $this->payouts->request($request->user(), (int) round(((float) $data['amount']) * 100), $data['phone']);

        if (is_string($result)) {
            return back()->withInput()->with(FlashAlertHelper::error('Cannot ask for this withdrawal', $result));
        }

        return back()->with(FlashAlertHelper::success('Withdrawal requested', 'We will send '.Money::formatMinor($result->net_minor, 0).' to your M-Pesa once staff approve it.'));
    }

    public function cancel(Request $request, Payout $payout)
    {
        $result = $this->payouts->cancel($payout, $request->user());

        return is_string($result)
            ? back()->with(FlashAlertHelper::error('Cannot cancel', $result))
            : back()->with(FlashAlertHelper::success('Withdrawal cancelled', 'The money is back in your available balance.'));
    }
}
