<?php

// Handles financial calculations for job applications, including service fees and net amounts.

namespace App\Helpers\Applications;

class ApplicationCalculationHelper
{
    // Service fee percentage as a constant
    const SERVICE_FEE_PERCENTAGE = 0.10; // 10%

    // Calculate service fee and net amount
    public static function calculateAmounts(float $offerAmount): array
    {
        $serviceFee = $offerAmount * self::SERVICE_FEE_PERCENTAGE;
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
        return self::SERVICE_FEE_PERCENTAGE;
    }

    // Calculate service fee only
    public static function calculateServiceFee(float $offerAmount): float
    {
        return $offerAmount * self::SERVICE_FEE_PERCENTAGE;
    }

    // Calculate net amount only
    public static function calculateNetAmount(float $offerAmount): float
    {
        return $offerAmount - self::calculateServiceFee($offerAmount);
    }
}
