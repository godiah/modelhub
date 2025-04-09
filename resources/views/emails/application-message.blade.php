@component('mail::message')
    # Message from {{ $employerName }}

    Regarding your application for: **{{ $jobTitle }}**

    {{ $message }}

    @component('mail::button', ['url' => route('my-applications.show', $applicationId)])
        View Application Details
    @endcomponent

    Thank you,<br>
    {{ config('app.name') }}
@endcomponent
