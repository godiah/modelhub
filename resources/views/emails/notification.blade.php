{{--
    The generic body for line-based emails built with App\Support\Mail\BrandedMail (subject, greeting, lines,
    one action). Laravel hands this view the MailMessage's own data.
--}}
@extends('emails.layouts.master')

@section('title', $heading ?: $subject)
@section('header_title', $heading ?: $subject)
@if (! empty($introLines))
    @section('preheader', \Illuminate\Support\Str::limit(strip_tags(implode(' ', $introLines)), 110))
@endif

@section('content')
    @if (! empty($greeting))
        <p>{{ $greeting }}</p>
    @endif

    @foreach ($introLines as $line)
        <p>{{ $line }}</p>
    @endforeach

    @isset($actionText)
        <x-mail.button :href="$actionUrl">{{ $actionText }}</x-mail.button>
    @endisset

    @foreach ($outroLines as $line)
        <p>{{ $line }}</p>
    @endforeach

    @isset($actionText)
        <p style="margin-top:24px; font-size:12px; line-height:1.6; color:#6B7280;">
            If the button does not work, copy this link into your browser:<br>
            <a href="{{ $actionUrl }}" style="color:#0F766E; word-break:break-all;">{{ $displayableActionUrl }}</a>
        </p>
    @endisset
@endsection
