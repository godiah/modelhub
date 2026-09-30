@extends('emails.layouts.master')

@section('title', 'Message from ' . $employerName)
@section('header_title', 'Message from ' . $employerName)
@section('preheader', \Illuminate\Support\Str::limit($messageBody, 110))

@section('content')
    <p>{{ $employerName }} sent you a message about your application for <strong>{{ $jobTitle }}</strong>.</p>

    <x-mail.callout>{{ $messageBody }}</x-mail.callout>

    <x-mail.button :href="$actionUrl">View your application</x-mail.button>
@endsection
