<?php

namespace App\Http\Controllers;

use App\Helpers\FlashAlertHelper;
use App\Models\JobEngagement;
use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;

/** The client pays a job's agreed amount into escrow: the M-Pesa prompt is sent here, and the waiting page takes over. */
class EngagementFundingController extends Controller
{
    public function store(Request $request, PaymentService $payments, JobEngagement $engagement)
    {
        abort_unless($request->user()->id === $engagement->application->poster_id, 404);

        $data = $request->validate(['phone' => ['required', 'string', 'max:20']]);
        $result = $payments->startEscrow($request->user(), $engagement, $data['phone']);

        if (is_string($result)) {
            return back()->withInput()->with(FlashAlertHelper::error('Cannot fund this job', $result));
        }

        return $result instanceof Payment && $result->status->isFinal()
            ? back()->with(FlashAlertHelper::error('The prompt was not sent', $result->failure_reason ?? 'Please try again.'))
            : redirect()->route('payments.show', $result);
    }
}
