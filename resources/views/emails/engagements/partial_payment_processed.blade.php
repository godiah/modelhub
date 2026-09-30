@extends('emails.layouts.master')

@section('title', 'Partial payment processed')
@section('header_title', 'Partial payment processed')
@section('preheader', 'A partial payment was processed for ' . $job->title . '.')

@section('content')
    <p>Hello {{ $notifiable->name }},</p>

    <p>A partial payment was processed for your cancelled engagement on <strong>{{ $job->title }}</strong>. It will be paid out on our standard payment schedule.</p>

    <x-mail.details>
        <x-mail.detail label="Amount"><x-money :amount="$payment->amount" /></x-mail.detail>
        <x-mail.detail label="Processed by">{{ $client->name }}</x-mail.detail>
        <x-mail.detail label="Processed on"><x-date :date="$payment->processed_at" format="F j, Y, g:i a" /></x-mail.detail>
    </x-mail.details>

    @if ($payment->notes)
        <x-mail.callout :title="__('Notes')">{{ $payment->notes }}</x-mail.callout>
    @endif

    <x-mail.button :href="route('engagements.show', $engagement)">View engagement</x-mail.button>
@endsection
