@extends('emails.layouts.master')

@section('title', 'Payment Disputed')

@section('header_title', 'Partial Payment Disputed')

@section('content')
    <div style="font-family: 'Inter', 'Roboto', sans-serif; color: #1F2937;">
        <p style="margin-bottom: 16px; color: #374151; font-size: 16px;">Hello {{ $notifiable->name }},</p>

        <p style="margin-bottom: 16px; color: #4B5563; font-size: 16px;">
            <span style="font-weight: 500;">{{ $freelancer->name }}</span> has disputed the partial payment for your
            cancelled engagement on the job: <span style="font-weight: 500;">"{{ $job->title }}"</span>. An
            administrator will review the case.
        </p>

        <p style="margin-bottom: 16px; color: #4B5563; font-size: 16px;">
            <strong style="color: #1E3A8A;">Disputed Amount:</strong>
            <span style="color: #1F2937; font-weight: 500;">
                Ksh{{ number_format($payment->amount, 2) }}
            </span>
        </p>

        <div style="margin-bottom: 20px; border-left: 4px solid #EF4444; padding-left: 16px; color: #4B5563;">
            <strong style="color: #1E3A8A; display: block; margin-bottom: 4px;">Reason:</strong>
            <p style="margin: 0; color: #4B5563;">{{ $dispute->formatted_reason }}</p>
        </div>

        <div style="margin: 30px 0; text-align: center;">
            <a href="{{ $actionUrl }}"
                style="display: inline-block; background-color: #F59E0B; color: white; font-family: 'Montserrat', sans-serif; font-weight: 500; text-decoration: none; padding: 12px 24px; border-radius: 6px; text-align: center; transition: background-color 0.2s;">
                View Engagement Details
            </a>
        </div>

        <p style="color: #6B7280; font-size: 14px;">&mdash; {{ config('app.name') }} Team</p>
    </div>
@endsection
