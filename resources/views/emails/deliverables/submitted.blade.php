@extends('emails.layouts.master')

@section('title', 'New Deliverable Submission')

@section('header_title', 'New Deliverable Submission')

@section('content')
    <p>
        <strong>{{ $deliverable->engagement->applicant->name }}</strong> has submitted a deliverable for
        the project
        <strong>{{ $deliverable->engagement->job->title }}</strong>.
    </p>

    <div class="mb-4">
        <h3 style="color: #1E3A8A; border-bottom: 1px solid #E5E7EB; padding-bottom: 8px;">Deliverable Details</h3>
        <p>
            <strong>Title:</strong> {{ $deliverable->title }}<br>
            <strong>Submitted On:</strong> {{ $deliverable->submitted_at->format('F j, Y \a\t g:i A') }}
        </p>
    </div>

    @if ($deliverable->submission_notes)
        <div class="mb-4">
            <h3 style="color: #1E3A8A; border-bottom: 1px solid #E5E7EB; padding-bottom: 8px;">Submission Notes</h3>
            <div style="background-color: #F9FAFB; padding: 12px; border-radius: 4px; border-left: 3px solid #14B8A6;">
                {{ $deliverable->submission_notes }}
            </div>
        </div>
    @endif

    @if ($deliverable->submission_files && count($deliverable->submission_files) > 0)
        <div class="mb-4">
            <h3 style="color: #1E3A8A; border-bottom: 1px solid #E5E7EB; padding-bottom: 8px;">Submitted Files</h3>
            <ul style="list-style-type: none; padding-left: 0;">
                @foreach ($deliverable->submission_files as $file)
                    <li style="margin-bottom: 8px; padding: 8px; background-color: #F9FAFB; border-radius: 4px;">
                        <span style="display: flex; align-items: center;">
                            <span style="margin-right: 8px; color: #6B7280;">📄</span>
                            {{ $file['name'] }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <p>Please review this submission at your earliest convenience.</p>
@endsection
