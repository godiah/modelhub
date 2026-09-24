@extends('emails.layouts.master')

@section('title', 'Engagement Cancelled')

@section('header_title', 'Engagement Cancelled')

@section('content')
    <p>
        The engagement for the job <strong>{{ $job->title }}</strong> has been cancelled by the {{ $initiatorType }}.
    </p>

    <div class="mb-4">
        <h3 style="color: #1E3A8A; border-bottom: 1px solid #E5E7EB; padding-bottom: 8px;">Cancellation Details</h3>
        <p>
            <strong>Initiator:</strong> {{ $initiator->name }} ({{ $initiatorType }})<br>
            <strong>Type:</strong> {{ ucfirst(str_replace('_', ' ', $cancellation->cancellation_type)) }}<br>
            <strong>Reason:</strong> {{ $cancellation->reason_category }}<br>
            <strong>Details:</strong> {{ $cancellation->reason_details }}<br>
            <strong>Date:</strong> {{ $cancellation->created_at->format('F j, Y, g:i A') }}
        </p>
    </div>

    @if ($cancellation->partial_payment_amount)
        <div class="mb-4">
            <strong>Partial Payment:</strong> ${{ number_format($cancellation->partial_payment_amount, 2) }}
        </div>
    @else
        @if ($notifiable->id === $applicant->id)
            <p>You may contact the client to request compensation for any completed work.</p>
        @else
            <p>You can compensate the freelancer for their work via a partial payment.</p>
            <a href="{{ $paymentUrl }}"
                style="display: inline-block; background-color: #2563EB; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none;">
                Process Partial Payment
            </a>
        @endif
    @endif

    <p style="margin-top: 24px;">
        <a href="{{ $actionUrl }}"
            style="display: inline-block; background-color: #4B5563; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none;">
            View Engagement Details
        </a>
    </p>
@endsection
