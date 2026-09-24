@extends('emails.layouts.master')

@section('title', 'Dispute Reported')

@section('header_title', 'New Dispute Reported')

@section('content')
    <p>
        A dispute has been reported for the engagement <strong>#{{ $engagement->id }}</strong> related to the job:
        <strong>{{ $job->title }}</strong>.
    </p>

    <div class="mb-4">
        <h3 style="color: #1E3A8A; border-bottom: 1px solid #E5E7EB; padding-bottom: 8px;">Dispute Information</h3>
        <p>
            <strong>Initiated By:</strong> {{ $initiator->name }}
            ({{ $initiator->id === $client->id ? 'Client' : 'Freelancer' }})<br>
            <strong>Reason Category:</strong> {{ $cancellation->reason_category }}<br>
            <strong>Details:</strong> {{ $cancellation->reason_details }}
        </p>
    </div>

    <div class="mb-4">
        <h3 style="color: #1E3A8A; border-bottom: 1px solid #E5E7EB; padding-bottom: 8px;">Involved Parties</h3>
        <p>
            <strong>Client:</strong> {{ $client->name }} ({{ $client->email }})<br>
            <strong>Freelancer:</strong> {{ $freelancer->name }} ({{ $freelancer->email }})
        </p>
    </div>

    <p style="margin-bottom: 16px;">
        Please review and take appropriate action as soon as possible.
    </p>

    <a href="{{ $actionUrl }}"
        style="
        display: inline-block;
        background-color: #2563EB;
        color: white;
        padding: 10px 20px;
        border-radius: 6px;
        text-decoration: none;
    ">
        View Dispute Details
    </a>

    <p style="margin-top: 24px;">
        This dispute requires resolution according to platform policies.
    </p>
@endsection
