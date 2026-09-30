@props(['tone' => 'neutral', 'title' => null])

{{-- A highlighted note (a message body, notes, a reason). tone: neutral | success | warning | danger. --}}
@php
    [$bar, $bg] = [
        'neutral' => ['#0D9488', '#F9FAFB'],
        'success' => ['#16A34A', '#F0FDF4'],
        'warning' => ['#D97706', '#FFFBEB'],
        'danger' => ['#DC2626', '#FEF2F2'],
    ][$tone];
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px;">
    <tr>
        <td style="background-color:{{ $bg }}; border-left:4px solid {{ $bar }}; border-radius:0 12px 12px 0; padding:14px 18px; font-size:15px; line-height:1.6; color:#374151;">
            @if ($title)
                <div style="margin-bottom:4px; font-size:13px; font-weight:600; color:#111827;">{{ $title }}</div>
            @endif
            <div style="white-space:pre-line; word-break:break-word;">{{ $slot }}</div>
        </td>
    </tr>
</table>
