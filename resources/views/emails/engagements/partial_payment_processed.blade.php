@extends('emails.layouts.master')

@section('title', 'Partial Payment')

@section('header_title', 'Partial Payment Processed')

@section('content')
    <div style="font-family: 'Inter', 'Roboto', sans-serif; color: #1F2937;">

        <p style="margin-bottom: 16px; color: #374151; font-size: 16px;">Hello {{ $notifiable->name }},</p>

        <p style="margin-bottom: 16px; color: #4B5563; font-size: 16px;">
            A partial payment has been processed for your cancelled engagement on the job:
            <span style="font-weight: 500;">"{{ $job->title }}"</span>.
        </p>

        <p style="margin-bottom: 16px; color: #4B5563; font-size: 16px;">
            <strong style="color: #1E3A8A;">Amount:</strong>
            <span style="color: #1F2937; font-weight: 500;">
                ${{ number_format($payment->amount, 2) }}
            </span>
        </p>

        <p style="margin-bottom: 16px; color: #4B5563; font-size: 16px;">
            <strong style="color: #1E3A8A;">Processed by:</strong>
            <span style="color: #1F2937; font-weight: 500;">{{ $client->name }}</span>
        </p>

        <p style="margin-bottom: 16px; color: #4B5563; font-size: 16px;">
            <strong style="color: #1E3A8A;">Processed on:</strong>
            <span style="color: #1F2937; font-weight: 500;">
                {{ $payment->processed_at->format('F j, Y, g:i a') }}
            </span>
        </p>

        @if ($payment->notes)
            <div style="margin-bottom: 20px; border-left: 4px solid #14B8A6; padding-left: 16px; color: #4B5563;">
                <strong style="color: #1E3A8A; display: block; margin-bottom: 4px;">Notes:</strong>
                <p style="margin: 0; color: #4B5563;">{{ $payment->notes }}</p>
            </div>
        @endif

        <div style="margin: 30px 0; text-align: center;">
            <a href="{{ url('/engagements/' . $engagement->id) }}"
                style="display: inline-block; background-color: #F59E0B; color: white; font-family: 'Montserrat', sans-serif; font-weight: 500; text-decoration: none; padding: 12px 24px; border-radius: 6px; text-align: center; transition: background-color 0.2s;">
                View Engagement Details
            </a>
        </div>

        <p style="margin-bottom: 16px; color: #4B5563; font-size: 16px;">
            The payment will be processed according to our standard payment schedule.
        </p>

        <p style="color: #6B7280; font-size: 14px;">&mdash; {{ config('app.name') }} Team</p>
    </div>
@endsection
