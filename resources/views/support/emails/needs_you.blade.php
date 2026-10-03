@extends('emails.layouts.master')

{{-- Support preview: a gentle reminder when we are the ones waiting. Sent once, after a few days of silence. --}}
@section('title', 'We are waiting to hear from you')
@section('header_title', 'We are waiting to hear from you')
@section('preheader', 'Request SUP-1042 cannot move on until you reply.')

@section('content')
    <p>Hi Achieng,</p>
    <p>We can't carry on with your request <strong>SUP-1042</strong> until we hear back from you. Grace asked:</p>

    <x-mail.callout tone="warning" :title="__('Waiting for you')">Could you confirm the phone number you paid from ends in 482?</x-mail.callout>

    <x-mail.button href="#">Reply</x-mail.button>
    <x-mail.button href="#" tone="secondary">It's sorted, close the request</x-mail.button>

    <p style="font-size:14px; color:#6B7280;">If we don't hear from you, we'll close the request in a few days. You can reopen it by replying any time after that.</p>
@endsection
