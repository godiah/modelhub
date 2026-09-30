@extends('emails.layouts.master')

@section('title', 'Partial payment disputed')
@section('header_title', 'Partial payment disputed')
@section('preheader', $freelancer->name . ' disputed the partial payment for ' . $job->title . '.')

@section('content')
    <p>Hello {{ $notifiable->name }},</p>

    <p><strong>{{ $freelancer->name }}</strong> disputed the partial payment for your cancelled engagement on <strong>{{ $job->title }}</strong>. An administrator will review the case.</p>

    <x-mail.details>
        <x-mail.detail label="Disputed amount"><x-money :amount="$payment->amount" /></x-mail.detail>
    </x-mail.details>

    <x-mail.callout tone="danger" :title="__('Reason')">{{ $dispute->formatted_reason }}</x-mail.callout>

    <x-mail.button :href="$actionUrl">View engagement</x-mail.button>
@endsection
