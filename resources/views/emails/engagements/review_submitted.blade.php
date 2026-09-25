@extends('emails.layouts.master')

@section('title', 'New Review')

@section('header_title', 'You Received a New Review')

@section('content')
    <div style="font-family: 'Inter', 'Roboto', sans-serif; color: #1F2937;">
        <p style="margin-bottom: 16px; color: #374151; font-size: 16px;">Hello {{ $notifiable->name }},</p>

        <p style="margin-bottom: 16px; color: #4B5563; font-size: 16px;">
            <span style="font-weight: 500;">{{ $reviewer->name }}</span> has left you a review for the job:
            <span style="font-weight: 500;">"{{ $job->title }}"</span>.
        </p>

        <div style="margin-bottom: 20px; text-align: center; padding: 16px; background-color: #F9FAFB; border-radius: 6px;">
            <span style="font-size: 24px; letter-spacing: 4px; color: #F59E0B;">
                {{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}
            </span>
            <p style="margin: 8px 0 0; color: #6B7280; font-size: 14px;">{{ $review->rating }} out of 5</p>
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
