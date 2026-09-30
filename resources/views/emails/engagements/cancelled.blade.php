@extends('emails.layouts.master')

@section('title', 'Engagement cancelled')
@section('header_title', 'Engagement cancelled')
@section('preheader', 'The engagement for ' . $job->title . ' was cancelled by the ' . $initiatorType . '.')

@section('content')
    <p>Hello {{ $notifiable->name }},</p>

    <p>The engagement for <strong>{{ $job->title }}</strong> was cancelled by the {{ $initiatorType }}.</p>

    <x-mail.details :title="__('Cancellation')">
        <x-mail.detail label="Cancelled by">{{ $initiator->name }} ({{ $initiatorType }})</x-mail.detail>
        <x-mail.detail label="Type">{{ ucfirst(str_replace('_', ' ', $cancellation->cancellation_type)) }}</x-mail.detail>
        <x-mail.detail label="Reason">{{ ucfirst(str_replace('_', ' ', $cancellation->reason_category)) }}</x-mail.detail>
        <x-mail.detail label="Date"><x-date :date="$cancellation->created_at" format="F j, Y, g:i A" /></x-mail.detail>
        @if ($cancellation->partial_payment_amount)
            <x-mail.detail label="Partial payment"><x-money :amount="$cancellation->partial_payment_amount" /></x-mail.detail>
        @endif
    </x-mail.details>

    @if ($cancellation->reason_details)
        <x-mail.callout :title="__('Details')">{{ $cancellation->reason_details }}</x-mail.callout>
    @endif

    @unless ($cancellation->partial_payment_amount)
        @if ($notifiable->id === $applicant->id)
            <p>You can contact the client to ask for compensation for any work you completed.</p>
        @else
            <p>You can compensate the freelancer for their work with a partial payment.</p>
            <x-mail.button :href="$paymentUrl">Process partial payment</x-mail.button>
        @endif
    @endunless

    <x-mail.button :href="$actionUrl" tone="secondary">View engagement</x-mail.button>
@endsection
