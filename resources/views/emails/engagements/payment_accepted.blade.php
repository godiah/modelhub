@extends('emails.layouts.master')

@section('title', 'Payment Accepted')

@section('header_title', 'Partial Payment Accepted')

@section('content')
    <div style="font-family: 'Inter', 'Roboto', sans-serif; color: #1F2937;">
        <p style="margin-bottom: 16px; color: #374151; font-size: 16px;">Hello {{ $notifiable->name }},</p>

        <p style="margin-bottom: 16px; color: #4B5563; font-size: 16px;">
            <span style="font-weight: 500;">{{ $freelancer->name }}</span> has accepted the partial payment for your
            cancelled engagement on the job: <span style="font-weight: 500;">"{{ $job->title }}"</span>.
        </p>

        <p style="margin-bottom: 16px; color: #4B5563; font-size: 16px;">
            <strong style="color: #1E3A8A;">Amount:</strong>
            <span style="color: #1F2937; font-weight: 500;">
                Ksh{{ number_format($payment->amount, 2) }}
            </span>
        </p>

        <p style="margin-bottom: 16px; color: #4B5563; font-size: 16px;">
            <strong style="color: #1E3A8A;">Accepted on:</strong>
            <span style="color: #1F2937; font-weight: 500;">
                {{ $payment->accepted_at?->format('F j, Y, g:i a') }}
            </span>
        </p>

        <div style="margin: 30px 0; text-align: center;">
            <a href="{{ $actionUrl }}"
                style="display: inline-block; background-color: #F59E0B; color: white; font-family: 'Montserrat', sans-serif; font-weight: 500; text-decoration: none; padding: 12px 24px; border-radius: 6px; text-align: center; transition: background-color 0.2s;">
                View Engagement Details
            </a>
        </div>

        <p style="color: #6B7280; font-size: 14px;">&mdash; {{ config('app.name') }} Team</p>
    </div>
@endsection
