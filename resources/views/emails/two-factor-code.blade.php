@extends('emails.layouts.master')

@section('title', 'Two-Factor Authentication')

@section('header_title', 'Two-Factor Authentication Code')

@section('content')
    <h2>Your verification code</h2>
    <p>Use this code to complete your login:</p>

    <div class="code">{{ $code }}</div>

    <p>This code will expire in 5 minutes.</p>
    <p>If you didn't request this code, please ignore this email.</p>
@endsection
