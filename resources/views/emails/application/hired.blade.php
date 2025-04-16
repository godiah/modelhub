@component('mail::message')
    # Congratulations! You've Been Hired

    Dear {{ $application->applicant->name }},

    We're pleased to inform you that you have been hired for the job: **{{ $application->job->title }}**.

    ## Job Details
    - **Job**: {{ $application->job->title }}
    - **Employer**: {{ $application->job->user->name }}
    - **Amount**: {{ config('app.currency_symbol') . number_format($engagement->agreed_amount, 2) }}

    @if ($engagement->deliverables->count() > 0)
        ## Deliverables
        @foreach ($engagement->deliverables as $deliverable)
            - **{{ $deliverable->title }}** - Due:
            {{ $deliverable->due_date ? date('M d, Y', strtotime($deliverable->due_date)) : 'No deadline' }}
        @endforeach
    @endif

    Please log in to your account to view the complete details of this engagement and start working on the project.

    @component('mail::button', ['url' => route('applications.show', $application->job->slug)])
        View Job Details
    @endcomponent

    Thank you for using our platform!

    Regards,<br>
    {{ config('app.name') }}
@endcomponent
