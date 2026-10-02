<?php

// Handles financial calculations for job applications, including service fees and net amounts. The rate comes from platform settings (FeePolicy) and is snapshotted on each application.

namespace App\Helpers\Applications;

use App\Support\Settings\FeePolicy;

class ApplicationCalculationHelper
{
    // Calculate service fee and net amount
    public static function calculateAmounts(float $offerAmount): array
    {
        $serviceFee = $offerAmount * FeePolicy::jobsRate();
        $netAmount = $offerAmount - $serviceFee;

        return [
            'offer_amount' => $offerAmount,
            'service_fee' => $serviceFee,
            'net_amount' => $netAmount,
        ];
    }

    // Get service fee percentage
    public static function getServiceFeePercentage(): float
    {
        return FeePolicy::jobsRate();
    }

    // Calculate service fee only
    public static function calculateServiceFee(float $offerAmount): float
    {
        return $offerAmount * FeePolicy::jobsRate();
    }

    // Calculate net amount only
    public static function calculateNetAmount(float $offerAmount): float
    {
        return $offerAmount - self::calculateServiceFee($offerAmount);
    }
}
