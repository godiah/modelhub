@extends('emails.layouts.master')

@section('title', 'Offer ' . $responseText)
@section('header_title', 'Your offer was ' . $responseText)
@section('preheader', $applicant->name . ' ' . $responseText . ' your offer for ' . $job->title . '.')

@section('content')
    <p>Hello {{ $notifiable->name }},</p>

    <p><strong>{{ $applicant->name }}</strong> {{ $responseText }} your offer for <strong>{{ $job->title }}</strong>.</p>

    <x-mail.details>
        <x-mail.detail label="Project">{{ $job->title }}</x-mail.detail>
        <x-mail.detail label="Agreed amount"><x-money :amount="$engagement->agreed_amount" /></x-mail.detail>
        <x-mail.detail label="Response">{{ ucfirst($responseText) }}</x-mail.detail>
    </x-mail.details>

    @if ($notes)
        <x-mail.callout :tone="$responseText === 'accepted' ? 'success' : 'danger'" :title="__('Notes')">{{ $notes }}</x-mail.callout>
    @endif

    <x-mail.button :href="$actionUrl">View engagement</x-mail.button>
@endsection
