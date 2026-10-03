@extends('emails.layouts.master')

{{-- Support preview: staff declined a refund. Gives the reason in their words and a way to be heard again. --}}
@section('title', 'About your refund request')
@section('header_title', 'About your refund request')
@section('preheader', 'Staff reviewed SUP-1043 and are not able to refund it this time.')

@section('content')
    <p>Hi Achieng,</p>
    <p>Staff looked at your request for a refund of <strong>Ksh510</strong> for <strong>Alarm Clock 01 – Clock Time PBR</strong> and decided not to refund it this time.</p>

    <x-mail.callout tone="neutral" :title="__('Their reason')">The files were downloaded three times and the purchase is outside the 7-day window. We could not see a problem with the file itself.</x-mail.callout>

    <p>This can be looked at again if you have something new, such as a screenshot of the error or the name of the software that would not open the file. Reply on your request page and a person will read it.</p>

    <x-mail.button href="#">View your request</x-mail.button>
@endsection
