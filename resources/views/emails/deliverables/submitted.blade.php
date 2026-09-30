@extends('emails.layouts.master')

@section('title', 'New deliverable submitted')
@section('header_title', 'New deliverable submitted')
@section('preheader', $deliverable->engagement->applicant->name . ' submitted "' . $deliverable->title . '".')

@section('content')
    <p><strong>{{ $deliverable->engagement->applicant->name }}</strong> submitted a deliverable for <strong>{{ $deliverable->engagement->job->title }}</strong>. Review it and approve it or ask for changes.</p>

    <x-mail.details :title="__('Deliverable')">
        <x-mail.detail label="Title">{{ $deliverable->title }}</x-mail.detail>
        <x-mail.detail label="Submitted">{{ $deliverable->submitted_at->format('F j, Y \a\t g:i A') }}</x-mail.detail>
        @if ($deliverable->submission_files && count($deliverable->submission_files) > 0)
            <x-mail.detail label="Files">
                @foreach ($deliverable->submission_files as $file)
                    {{ $file['name'] }}@unless ($loop->last)<br>@endunless
                @endforeach
            </x-mail.detail>
        @endif
    </x-mail.details>

    @if ($deliverable->submission_notes)
        <x-mail.callout :title="__('Notes from the freelancer')">{{ $deliverable->submission_notes }}</x-mail.callout>
    @endif

    <x-mail.button :href="route('engagements.show', $deliverable->engagement)">Review the submission</x-mail.button>
@endsection
