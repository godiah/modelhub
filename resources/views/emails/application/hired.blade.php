@extends('emails.layouts.master')

@section('title', 'Congratulations! You\'ve Been Hired')

@section('header_title', 'Congratulations! You\'ve Been Hired')

@section('content')
<h2>Dear {{ $application->applicant->name }},</h2>

<p>
    We're pleased to inform you that you have been hired for the job:
    <strong>{{ $application->job->title }}</strong>.
</p>

<div class="mb-4">
    <h3 style="color: #1E3A8A; border-bottom: 1px solid #E5E7EB; padding-bottom: 8px;">Job Details</h3>
    <ul style="list-style-type: none; padding-left: 0;">
        <li style="margin-bottom: 8px;">
            <strong>Job:</strong> {{ $application->job->title }}
        </li>
        <li style="margin-bottom: 8px;">
            <strong>Employer:</strong> {{ $application->job->user->name }}
        </li>
        <li style="margin-bottom: 8px;">
            <strong>Amount:</strong> {{ config('app.currency_symbol') . number_format($engagement->agreed_amount, 2) }}
        </li>
    </ul>
</div>

@if ($engagement->deliverables->count() > 0)
<div class="mb-4">
    <h3 style="color: #1E3A8A; border-bottom: 1px solid #E5E7EB; padding-bottom: 8px;">Deliverables</h3>
    <ul style="list-style-type: none; padding-left: 0;">
        @foreach ($engagement->deliverables as $deliverable)
        <li
            style="margin-bottom: 12px; padding: 8px; background-color: #F9FAFB; border-left: 3px solid #14B8A6; padding-left: 12px;">
            <strong>{{ $deliverable->title }}</strong><br>
            <span style="color: #6B7280; font-size: 14px;">Due:
                {{ $deliverable->due_date ? date('M d, Y', strtotime($deliverable->due_date)) : 'No deadline' }}
            </span>
        </li>
        @endforeach
    </ul>
</div>
@endif

<p>
    Please log in to your account to view the complete details of this engagement and start working on the project.
</p>

<div class="text-center">
    <a href="{{ route('applications.show', $application->job->slug) }}" class="btn btn-accent">View Job Details</a>
</div>
@endsection