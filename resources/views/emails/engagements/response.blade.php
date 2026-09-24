@extends('emails.layouts.master')

@section('title', 'New Engagement')

@section('header_title', 'Engagement Response Notification')

@section('content')
    <div style="font-family: 'Inter', 'Roboto', sans-serif; color: #1F2937;">
        <h2 style="color: #1E3A8A; font-family: 'Montserrat', sans-serif; font-weight: 600; margin-bottom: 20px;">Engagement
            Response Notification</h2>

        <p style="margin-bottom: 16px; color: #374151; font-size: 16px;">Hello {{ $notifiable->name }},</p>

        <p style="margin-bottom: 16px; color: #4B5563; font-size: 16px;">
            <span style="font-weight: 500;">{{ $applicant->name }}</span> has <span
                style="color: {{ $responseText == 'accepted' ? '#14B8A6' : '#EF4444' }}; font-weight: 500;">{{ $responseText }}</span>
            your job engagement offer for "<span style="font-weight: 500;">{{ $job->title }}</span>".
        </p>

        <p style="margin-bottom: 16px; color: #4B5563; background-color: #F3F4F6; padding: 12px; border-radius: 6px;">
            <strong style="color: #1E3A8A;">Agreed Amount:</strong> <span
                style="color: #1F2937; font-weight: 500;">${{ number_format($engagement->agreed_amount, 2) }}</span>
        </p>

        @if ($notes)
            <div style="margin-bottom: 20px; border-left: 4px solid #14B8A6; padding-left: 16px; color: #4B5563;">
                <strong style="color: #1E3A8A; display: block; margin-bottom: 4px;">Notes:</strong>
                <p style="margin: 0; color: #4B5563;">{{ $notes }}</p>
            </div>
        @endif

        <div style="margin: 30px 0; text-align: center;">
            <a href="{{ $actionUrl }}"
                style="display: inline-block; background-color: #F59E0B; color: white; font-family: 'Montserrat', sans-serif; font-weight: 500; text-decoration: none; padding: 12px 24px; border-radius: 6px; text-align: center; transition: background-color 0.2s;">
                View Engagement Details
            </a>
        </div>
    </div>
@endsection
