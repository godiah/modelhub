@extends('emails.layouts.master')

@section('title', 'Partial payment accepted')
@section('header_title', 'Partial payment accepted')
@section('preheader', $freelancer->name . ' accepted the partial payment for ' . $job->title . '.')

@section('content')
    <p>Hello {{ $notifiable->name }},</p>

    <p><strong>{{ $freelancer->name }}</strong> accepted the partial payment for your cancelled engagement on <strong>{{ $job->title }}</strong>.</p>

    <x-mail.details>
        <x-mail.detail label="Amount"><x-money :amount="$payment->amount" /></x-mail.detail>
        <x-mail.detail label="Accepted on">{{ $payment->accepted_at?->format('F j, Y, g:i a') }}</x-mail.detail>
    </x-mail.details>

    <x-mail.button :href="$actionUrl">View engagement</x-mail.button>
@endsection
