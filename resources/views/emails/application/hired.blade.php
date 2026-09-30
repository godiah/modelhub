@extends('emails.layouts.master')

@section('title', 'You have been hired')
@section('header_title', 'You have been hired')
@section('preheader', 'The client accepted your application for ' . $application->job->title . '.')

@section('content')
    <p>Hello {{ $application->applicant->name }},</p>

    <p>Good news: <strong>{{ $application->job->user->name }}</strong> wants to hire you for <strong>{{ $application->job->title }}</strong>. Review the offer and accept it to get started.</p>

    <x-mail.details :title="__('The offer')">
        <x-mail.detail label="Project">{{ $application->job->title }}</x-mail.detail>
        <x-mail.detail label="Client">{{ $application->job->user->name }}</x-mail.detail>
        <x-mail.detail label="Amount"><x-money :amount="$engagement->agreed_amount" /></x-mail.detail>
    </x-mail.details>

    @if ($engagement->deliverables->count() > 0)
        <x-mail.details :title="__('Deliverables')">
            @foreach ($engagement->deliverables as $deliverable)
                <x-mail.detail :label="$deliverable->title">{{ $deliverable->due_date ? 'Due '.date('M j, Y', strtotime($deliverable->due_date)) : 'No deadline' }}</x-mail.detail>
            @endforeach
        </x-mail.details>
    @endif

    <x-mail.button :href="route('engagements.response-form', $application->id)">Review the offer</x-mail.button>
@endsection
