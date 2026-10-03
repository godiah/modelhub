@extends('emails.layouts.master')

{{-- Support preview: staff approved a refund. Says what is done, what is NOT done yet (the money), and promises no timing. --}}
@section('title', 'Your refund has been recorded')
@section('header_title', 'Your refund has been recorded')
@section('preheader', 'Alarm Clock 01 – Clock Time PBR, Ksh510. The money is returned by hand.')

@section('content')
    <p>Hi Achieng,</p>
    <p>Staff looked at your refund request for <strong>Alarm Clock 01 – Clock Time PBR</strong> and approved it.</p>

    <x-mail.details :title="__('Your refund')">
        <x-mail.detail label="Amount">Ksh510</x-mail.detail>
        <x-mail.detail label="Reference">SUP-1043</x-mail.detail>
        <x-mail.detail label="Your licence">Ended. You can no longer download the files.</x-mail.detail>
    </x-mail.details>

    <x-mail.callout tone="success" :title="__('What happens next')">The money is returned by hand to the M-Pesa number you paid from (07•• ••• 482). We can't give an exact time. If it hasn't arrived and you are worried, reply on your request page and we will check.</x-mail.callout>

    <x-mail.button href="#">View your request</x-mail.button>
@endsection
