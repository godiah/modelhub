<?php

namespace App\Services\Ledger;

use Illuminate\Support\Facades\DB;

/**
 * The ledger read as a whole, for staff. Everything is worked out from the entries: where the money sits, what is owed to members, what the
 * platform has earned, and whether those add up (what the gateway holds must equal what is owed plus what has been earned).
 */
class LedgerReport
{
    public function __construct(protected LedgerService $ledger) {}

    /**
     * @return array{
     *     gateway: int, pending: int, available: int, in_payouts: int, unallocated: int, commission: int, fees: int,
     *     owed: int, earned: int, expected: int, difference: int, reconciled: bool, trial: array{debits: int, credits: int, balanced: bool}
     * }
     */
    public function summary(): array
    {
        $net = DB::table('ledger_entries as e')->join('ledger_accounts as a', 'a.id', '=', 'e.account_id')
            ->selectRaw("a.code, a.kind, sum(case when e.direction = 'debit' then e.amount_minor else -e.amount_minor end) as net")->groupBy('a.id', 'a.code', 'a.kind')->get();

        // What each account holds, positive when it holds money, whichever kind it is
        $held = fn (object $row) => $row->kind === 'asset' ? (int) $row->net : -(int) $row->net;

        $platform = fn (string $key) => $net->where('code', "platform.{$key}")->map($held)->sum();
        $members = fn (string $which) => $net->filter(fn ($row) => str_starts_with($row->code, 'user.') && str_ends_with($row->code, ".{$which}"))->map($held)->sum();

        $gateway = (int) $platform('gateway');
        $pending = (int) $members('pending');
        $available = (int) $members('available');
        $inPayouts = (int) $platform('payout_clearing');
        $unallocated = (int) $platform('suspense');
        $commission = (int) $platform('revenue');
        $fees = (int) $platform('payout_fees');

        $owed = $pending + $available + $inPayouts + $unallocated;
        $earned = $commission + $fees;
        $expected = $owed + $earned;

        return [
            'gateway' => $gateway, 'pending' => $pending, 'available' => $available, 'in_payouts' => $inPayouts, 'unallocated' => $unallocated,
            'commission' => $commission, 'fees' => $fees, 'owed' => $owed, 'earned' => $earned, 'expected' => $expected,
            'difference' => $gateway - $expected, 'reconciled' => $gateway === $expected, 'trial' => $this->ledger->trialBalance(),
        ];
    }
}
