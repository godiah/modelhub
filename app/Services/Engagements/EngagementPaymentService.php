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

    // Get latest payment for engagement
    public function getLatestPayment(JobEngagement $engagement)
    {
        return $engagement->partialPayments()->latest()->first();
    }
}
