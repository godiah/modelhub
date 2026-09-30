@extends('emails.layouts.master')

@section('title', 'Your verification code')
@section('header_title', 'Your verification code')
@section('preheader', 'Use this code to finish signing in. It expires in 5 minutes.')

@section('content')
    <p>Use this code to finish signing in:</p>

    <x-mail.code>{{ $code }}</x-mail.code>

    <p>It expires in 5 minutes. If you did not try to sign in, you can ignore this email and your account stays safe.</p>
@endsection
