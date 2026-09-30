@extends('emails.layouts.master')

@section('title', 'New dispute reported')
@section('header_title', 'New dispute reported')
@section('preheader', 'Engagement #' . $engagement->id . ' (' . $job->title . ') needs review.')

@section('content')
    <p>A dispute was reported on engagement <strong>#{{ $engagement->id }}</strong> for <strong>{{ $job->title }}</strong>. Please review it and take action as soon as you can.</p>

    <x-mail.details :title="__('Dispute')">
        <x-mail.detail label="Reported by">{{ $initiator->name }} ({{ $initiator->id === $client->id ? 'Client' : 'Freelancer' }})</x-mail.detail>
        <x-mail.detail label="Reason">{{ ucfirst(str_replace('_', ' ', $cancellation->reason_category)) }}</x-mail.detail>
    </x-mail.details>

    @if ($cancellation->reason_details)
        <x-mail.callout tone="warning" :title="__('Details')">{{ $cancellation->reason_details }}</x-mail.callout>
    @endif

    <x-mail.details :title="__('Parties')">
        <x-mail.detail label="Client">{{ $client->name }}<br>{{ $client->email }}</x-mail.detail>
        <x-mail.detail label="Freelancer">{{ $freelancer->name }}<br>{{ $freelancer->email }}</x-mail.detail>
    </x-mail.details>

    <x-mail.button :href="$actionUrl">View the dispute</x-mail.button>
@endsection
