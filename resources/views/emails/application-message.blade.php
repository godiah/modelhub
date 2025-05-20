@extends('emails.layouts.master')

@section('title', 'Message from ' . $employerName)

@section('header_title', 'Message from ' . $employerName)

@section('content')
<div class="mb-4">
    <h3 style="color: #1E3A8A;">Regarding your application for: <strong>{{ $jobTitle }}</strong></h3>
</div>

<div class="mb-4" style="background-color: #F9FAFB; padding: 20px; border-radius: 6px; border-left: 4px solid #14B8A6;">
    {{ $message }}
</div>

@endsection