@extends('emails.layouts.master')

{{-- Support preview: an UNVERIFIED account-access request from the contact form. Links to nothing, asks for nothing, and says
     exactly what we will and will not do. --}}
@section('title', 'We need to check it is you')
@section('header_title', 'We need to check it is you')
@section('preheader', 'About request SUP-1051. Nothing on any account has changed.')

@section('content')
    <p>Hello,</p>
    <p>Someone used this email address to ask for help getting into a ModelHub account (reference <strong>SUP-1051</strong>). If that was you, this is what happens next:</p>

    <ol>
        <li>We contact you using the email or phone number <strong>already on the account</strong>, not the ones typed into the form.</li>
        <li>We check it is really you.</li>
        <li>Then we help you back in.</li>
    </ol>

    <x-mail.callout tone="warning" :title="__('Staying safe')">We will never ask for your password, your M-Pesa PIN, or a code sent by SMS or email. If someone does, it is not us.</x-mail.callout>

    <p>If this wasn't you, you can ignore this email. Nothing on any account has changed.</p>
@endsection

@section('footer')
    <p style="margin:0 0 6px;">You are receiving this because this address was typed into the ModelHub contact form. Reference SUP-1051.</p>
    <p style="margin:0;">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
@endsection
