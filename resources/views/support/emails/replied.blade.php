@extends('emails.layouts.master')

{{-- Support preview: a staff member replied on a request. The reply is quoted so it reads on its own. --}}
@section('title', 'Grace replied to your request')
@section('header_title', 'Grace replied to your request')
@section('preheader', 'Hi Achieng, thanks for sending this. I can see your payment arrived but the licence wasn\'t created.')

@section('content')
    <p>Hi Achieng,</p>
    <p>There is a new reply on your request <strong>SUP-1042</strong>.</p>

    <x-mail.callout tone="neutral" :title="__('Grace · ModelHub support')">Hi Achieng, thanks for sending this. I can see your payment arrived but the licence wasn't created. I'm checking it now.<br><br>Could you confirm the phone number you paid from ends in 482?</x-mail.callout>

    <x-mail.button href="#">Reply on your request page</x-mail.button>

    <p style="font-size:14px; color:#6B7280;">Replies sent to this email address are not read. Please answer on your request page.</p>
@endsection
