<?php

namespace App\Support\Settings;

use App\Models\SellerProfile;

/**
 * The commission and money rules in force, read from platform settings. Callers take a rate here at the moment a transaction is made
 * and store it on that transaction, so a later change to the setting never rewrites what was already agreed.
 */
final class FeePolicy
{
    /** The commission on a job, as a fraction (10% is 0.10). */
    public static function jobsRate(): float
    {
        return self::fraction(PlatformSettings::float('fees.jobs_percent'));
    }

    /** The commission on a model sale, as a fraction: the seller's own rate when they have one, otherwise the platform's. */
    public static function modelsRate(?SellerProfile $seller = null): float
    {
        return self::fraction($seller?->commission_percent !== null ? (float) $seller->commission_percent : PlatformSettings::float('fees.models_percent'));
    }

    /** The lowest price a paid model may have, in minor units (KES cents). */
    public static function minModelPriceMinor(): int
    {
        return PlatformSettings::int('fees.min_model_price') * 100;
    }

    /** The smallest withdrawal, in minor units. */
    public static function minPayoutMinor(): int
    {
        return PlatformSettings::int('fees.min_payout') * 100;
    }

    /** The fee taken from each withdrawal, in minor units. */
    public static function payoutFeeMinor(): int
    {
        return PlatformSettings::int('fees.payout_fee') * 100;
    }

    public static function saleHoldDays(): int
    {
        return PlatformSettings::int('fees.sale_hold_days');
    }

    private static function fraction(float $percent): float
    {
        return round($percent / 100, 4);
    }
}
