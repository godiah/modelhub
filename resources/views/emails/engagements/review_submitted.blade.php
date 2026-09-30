@extends('emails.layouts.master')

@section('title', 'New review')
@section('header_title', 'You received a new review')
@section('preheader', $reviewer->name . ' rated you ' . $review->rating . ' out of 5 for ' . $job->title . '.')

@section('content')
    <p>Hello {{ $notifiable->name }},</p>

    <p><strong>{{ $reviewer->name }}</strong> reviewed your work on <strong>{{ $job->title }}</strong>.</p>

    <x-mail.details>
        <x-mail.detail label="Rating">
            <span style="color:#D97706; letter-spacing:2px;">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span>
            <span style="color:#6B7280; font-weight:400;"> {{ $review->rating }} out of 5</span>
        </x-mail.detail>
    </x-mail.details>

    <x-mail.button :href="$actionUrl">View engagement</x-mail.button>
@endsection
