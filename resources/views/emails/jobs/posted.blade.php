@extends('emails.layouts.master')

@section('title', 'Your project is live')
@section('header_title', 'Your project is live')
@section('preheader', $job->title . ' is now open for applications.')

@section('content')
    <p>Hello {{ $job->user->name }},</p>

    <p><strong>{{ $job->title }}</strong> has been posted and is open for applications. Freelancers can now view it and send you their offers.</p>

    <x-mail.details :title="__('Project summary')">
        <x-mail.detail label="Title">{{ $job->title }}</x-mail.detail>
        <x-mail.detail label="Posted"><x-date :date="$job->created_at" format="F j, Y" /></x-mail.detail>
        @if ($job->budget)
            <x-mail.detail label="Budget"><x-money :amount="$job->budget" :decimals="0" /></x-mail.detail>
        @endif
    </x-mail.details>

    <x-mail.button :href="route('jobs.show', $job->slug)">View your project</x-mail.button>
@endsection
