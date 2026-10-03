@extends('emails.layouts.master')

{{-- Support preview: sent the moment a request is created, from the chat or the contact form. Sample values only. --}}
@section('title', 'We have your request')
@section('header_title', 'We have your request')
@section('preheader', 'Reference SUP-1042. A person will reply within 4 business hours.')

@section('content')
    <p>Hi Achieng,</p>
    <p>Your request about <strong>a payment that needs review</strong> reached our team. A person will read it and reply.</p>

    <x-mail.details :title="__('Your request')">
        <x-mail.detail label="Reference">SUP-1042</x-mail.detail>
        <x-mail.detail label="First reply">Within 4 business hours (weekdays, 8am to 6pm EAT)</x-mail.detail>
        <x-mail.detail label="Replies arrive">By email, and on your request page</x-mail.detail>
    </x-mail.details>

    <x-mail.callout tone="neutral" :title="__('What we sent')">Paid Ksh1,500 for Large Iron Gate. Payment received, licence not created.</x-mail.callout>

    <x-mail.button href="#">View your request</x-mail.button>

    <p style="font-size:14px; color:#6B7280;">Please never send your M-Pesa PIN, a password or a code from an SMS. We will never ask for them.</p>
@endsection
