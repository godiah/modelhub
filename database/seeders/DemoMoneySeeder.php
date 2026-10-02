<?php

namespace Database\Seeders;

use App\Enums\GatewayState;
use App\Enums\LicenceTier;
use App\Models\LedgerTransaction;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Staff;
use App\Models\User;
use App\Services\Ledger\LedgerService;
use App\Services\Payments\EarningsService;
use App\Services\Payments\PaymentService;
use App\Services\Payments\PayoutService;
use App\Services\Payments\RefundService;
use App\Support\Payments\PaymentOutcome;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Local demo money for the staff Payments, Ledger and Withdrawals screens and the seller Earnings page: about two dozen sales
 * spread over three weeks (some still in the hold, some released), two refunds, two payments that arrived in the wrong amount,
 * a pending prompt, failed attempts, and withdrawals in each state. Everything goes through the real services and the fake
 * gateway, so the ledger is genuinely balanced.
 *
 *   php artisan db:seed --class=DemoMoneySeeder
 *
 * Never runs in production, and does nothing if any payment already exists (ledger entries cannot be deleted, so there is no
 * re-run). Needs DemoModelsSeeder's sellers and buyers first.
 */
class DemoMoneySeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction() || Payment::exists()) {
            $this->command?->warn('Skipped: this is production, or payments already exist.');

            return;
        }

        $buyers = User::where('email', 'like', 'demo.buyer%@demo.test')->orderBy('id')->get();
        $sellers = User::where('email', 'like', 'demo.seller%@demo.test')->orderBy('id')->get();
        $staff = Staff::where('email', 'admin@modelhub.com')->first();

        if ($buyers->isEmpty() || $sellers->isEmpty() || ! $staff) {
            $this->command?->warn('Skipped: run DemoModelsSeeder first (needs demo buyers, sellers and the admin staff account).');

            return;
        }

        config(['payments.fake.delay_seconds' => 0]);
        mt_srand(7);

        // Some listings get an Extended licence, so both tiers show up
        $products = Product::with('sellerProfile')->published()->whereIn('user_id', $sellers->pluck('id'))->where('price_minor', '>=', 10000)->orderBy('id')->get();
        foreach ($products->filter(fn ($p, $i) => $i % 3 === 0) as $product) {
            $product->update(['extended_price_minor' => (int) (round($product->price_minor * 3 / 100) * 100)]);
        }
        $products = Product::with('sellerProfile')->published()->whereIn('user_id', $sellers->pluck('id'))->where('price_minor', '>=', 10000)->orderBy('id')->get();

        $payments = app(PaymentService::class);
        $sales = [];

        foreach ([21, 19, 17, 16, 15, 13, 12, 11, 10, 9, 8, 8, 6, 5, 5, 4, 3, 3, 2, 2, 1, 1, 0, 0] as $i => $daysAgo) {
            Carbon::setTestNow(now()->startOfDay()->addHours(8 + $i % 9)->subDays($daysAgo));
            $buyer = $buyers[$i % $buyers->count()];

            foreach ($products->shuffle() as $product) {
                $tier = $i % 4 === 0 && $product->extended_price_minor ? LicenceTier::Extended : LicenceTier::Standard;
                $started = $payments->start($buyer, $product, $tier, '07'.mt_rand(10, 99).'12345'.mt_rand(5, 9));

                if ($started instanceof Payment) {
                    $sale = $payments->refresh($started);
                    $sales[] = $sale;

                    break;
                }
            }
            Carbon::setTestNow();
        }

        // Failed attempts: insufficient funds, cancelled on the phone
        foreach ([1, 2] as $k => $digit) {
            Carbon::setTestNow(now()->subDays(2 + $k)->subHours(3));
            $this->attempt($payments, $buyers[$k + 2], $products, "071234567{$digit}");
            Carbon::setTestNow();
        }

        // A prompt still waiting for the buyer (the fake gateway keeps it pending for ten minutes)
        config(['payments.fake.delay_seconds' => 600]);
        $this->attempt($payments, $buyers[4], $products, '0712345678');
        config(['payments.fake.delay_seconds' => 0]);

        // Money that arrived in the wrong amount, parked for staff: one is still waiting, one gets returned
        $parked = [];
        foreach ([[2, 5000, $buyers[5]], [4, -3000, $buyers[0]]] as [$daysAgo, $difference, $buyer]) {
            Carbon::setTestNow(now()->subDays($daysAgo)->subHours(2));

            foreach ($products->shuffle() as $product) {
                $started = $payments->start($buyer, $product, LicenceTier::Standard, '0722123456');

                if ($started instanceof Payment) {
                    $parked[] = $payments->applyOutcome($started, new PaymentOutcome($started->gateway_reference, GatewayState::Succeeded, 'ODD'.mt_rand(1000000, 9999999), $started->amount_minor + $difference));

                    break;
                }
            }
            Carbon::setTestNow();
        }

        // Sales older than the hold become withdrawable
        app(EarningsService::class)->releaseDue();

        // Refunds: one old sale after its release, one recent sale still in the hold, and one of the parked payments
        $refunds = app(RefundService::class);
        $old = collect($sales)->first(fn (Payment $p) => $p->created_at->lt(now()->subDays(9)));
        $recent = collect($sales)->first(fn (Payment $p) => $p->created_at->gt(now()->subDays(4)));
        $old && $refunds->refund($old, $staff, 'The model is not as described: the listing shows a rigged version but the file is a static mesh.');
        $recent && $refunds->refund($recent, $staff, 'The file would not open in Blender, and the seller could not supply a working one.');
        $parked[1] && $refunds->refund($parked[1], $staff, 'Paid a different amount from the price. Money returned to the buyer by M-Pesa.');

        // Withdrawals in each state: paid, waiting for approval, turned down, cancelled by the seller
        $payouts = app(PayoutService::class);
        $ledger = app(LedgerService::class);
        $steps = [
            fn ($p) => $payouts->refresh($payouts->approve($p, $staff)),
            fn ($p) => $p,
            fn ($p) => $payouts->reject($p, $staff, 'The phone number does not match the name on the store. Please send one registered to you.'),
            fn ($p) => $payouts->cancel($p, $p->user),
        ];

        foreach ($sellers->sortByDesc(fn (User $s) => $ledger->balances($s)['available']) as $n => $seller) {
            $available = $ledger->balances($seller)['available'];
            $amount = (int) (floor($available * ($n === 0 ? 0.6 : 1) / 10000) * 10000);

            if ($amount < 50000 || ! isset($steps[$n])) {
                continue;
            }

            $payout = $payouts->request($seller, $amount, '0712345675');

            if (! is_string($payout)) {
                $steps[$n]($payout);
            }
        }

        // The first seller asks for a second withdrawal that stays waiting, so the approval queue has something in it
        $first = $sellers->sortByDesc(fn (User $s) => $ledger->balances($s)['available'])->first();
        $left = (int) (floor($ledger->balances($first)['available'] / 10000) * 10000);
        $left >= 50000 && $payouts->request($first, $left, '0712345675');

        $this->command?->info(sprintf('Seeded %d payments, %d postings. Books balanced: %s.', Payment::count(), LedgerTransaction::count(), $ledger->trialBalance()['balanced'] ? 'yes' : 'NO'));
    }

    private function attempt(PaymentService $payments, User $buyer, $products, string $phone): void
    {
        foreach ($products->shuffle() as $product) {
            $started = $payments->start($buyer, $product, LicenceTier::Standard, $phone);

            if ($started instanceof Payment) {
                $payments->refresh($started);

                return;
            }
        }
    }
}
