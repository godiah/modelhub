@extends('emails.layouts.master')

@section('title', 'Project Posted Successfully')

@section('header_title', 'Project Posted Successfully!')

@section('content')
<h2>Hello {{ $job->user->name }}!</h2>

<p>
    Great news! Your project titled <strong>{{ $job->title }}</strong> has been posted successfully.
</p>

<p>
    Your project is now visible to potential candidates, and you can start receiving applications from qualified
    professionals.
</p>

<div class="text-center">
    <a href="{{ route('jobs.show', $job->slug) }}" class="btn btn-primary">View Your Project</a>
</div>

<div class="mt-4">
    <p>Here's a quick summary of your project:</p>
    <ul>
        <li><strong>Title:</strong> {{ $job->title }}</li>
        <li><strong>Posted on:</strong> {{ $job->created_at->format('F j, Y') }}</li>
        @if($job->budget)
        <li><strong>Budget:</strong> {{ $job->budget }}</li>
        @endif
    </ul>
</div>
@endsection