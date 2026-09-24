<?php

/**
 * EngagementPaymentService
 *
 * Handles payment processing for cancelled engagements.
 * Acts as a wrapper around PartialPaymentService with engagement-specific logic.
 */

namespace App\Services\Engagements;

use App\Models\JobEngagement;
use App\Services\Payments\PartialPaymentService;

class EngagementPaymentService
{
    protected PartialPaymentService $partialPaymentService;

    public function __construct(PartialPaymentService $partialPaymentService)
    {
        $this->partialPaymentService = $partialPaymentService;
    }

    // Get payment information using PartialPaymentService
    public function getPaymentInfo(JobEngagement $engagement): array
    {
        return $this->partialPaymentService->canProcessPayment($engagement);
    }

    // Check if payment can be processed
    public function canProcessPayment(JobEngagement $engagement): bool
    {
        $paymentInfo = $this->getPaymentInfo($engagement);

        return $paymentInfo['can_process'];
    }

    // Get latest payment for engagement
    public function getLatestPayment(JobEngagement $engagement)
    {
        return $engagement->partialPayments()->latest()->first();
    }

    // Calculate partial payment amount
    public function calculatePartialPayment(JobEngagement $engagement): float
    {
        return $this->partialPaymentService->calculatePartialPayment($engagement);
    }
}
