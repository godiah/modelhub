@extends('emails.layouts.master')

{{-- Support preview: sent when staff resolve a request. Asks how we did, but never blocks reopening. --}}
@section('title', 'Your request is resolved')
@section('header_title', 'Your request is resolved')
@section('preheader', 'SUP-1042 is marked resolved. Reply if something is still wrong.')

@section('content')
    <p>Hi Achieng,</p>
    <p>Grace marked your request <strong>SUP-1042</strong> as resolved.</p>

    <x-mail.details :title="__('What happened')">
        <x-mail.detail label="Request">A payment that needs review</x-mail.detail>
        <x-mail.detail label="Outcome">The payment was matched to your purchase and your licence was issued. Your download is unlocked.</x-mail.detail>
    </x-mail.details>

    <x-mail.button href="#">View your request</x-mail.button>

    <p>If something is still wrong, reply on your request page and we will pick it up again.</p>

    <p style="margin-bottom:8px;"><strong>How did we do?</strong></p>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
        <td style="padding-right:10px;"><x-mail.button href="#" tone="secondary">That helped</x-mail.button></td>
        <td><x-mail.button href="#" tone="secondary">Not really</x-mail.button></td>
    </tr></table>
@endsection
