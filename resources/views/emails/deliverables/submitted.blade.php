@component('mail::message')
    # New Deliverable Submission

    **{{ $deliverable->engagement->freelancer->name }}** has submitted a deliverable for the project
    **{{ $deliverable->engagement->job->title }}**.

    ## Deliverable Details
    **Title:** {{ $deliverable->title }}
    **Submitted On:** {{ $deliverable->submitted_at->format('F j, Y \a\t g:i A') }}

    @if ($deliverable->submission_notes)
        ## Submission Notes
        {{ $deliverable->submission_notes }}
    @endif

    @if ($deliverable->submission_files && count($deliverable->submission_files) > 0)
        ## Submitted Files
        @foreach ($deliverable->submission_files as $file)
            - {{ $file['name'] }}
        @endforeach
    @endif

    Please review this submission at your earliest convenience.

    @component('mail::button', ['url' => route('engagements.show', $deliverable->engagement_id)])
        View Deliverable
    @endcomponent

    Thank you,<br>
    {{ config('app.name') }}
@endcomponent
