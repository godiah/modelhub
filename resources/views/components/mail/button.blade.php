@props(['href', 'tone' => 'primary', 'align' => 'left'])

{{-- Bulletproof call-to-action for emails. tone: primary (teal) | secondary (outlined) | danger. --}}
@php
    $style = [
        'primary' => 'background-color:#0D9488; border:1px solid #0D9488; color:#ffffff;',
        'secondary' => 'background-color:#ffffff; border:1px solid #D1D5DB; color:#1F2937;',
        'danger' => 'background-color:#DC2626; border:1px solid #DC2626; color:#ffffff;',
    ][$tone];
@endphp
<table role="presentation" cellpadding="0" cellspacing="0" border="0" @if ($align === 'center') align="center" @endif style="margin:8px 0 20px;">
    <tr>
        <td style="border-radius:10px; {{ $style }}">
            <a href="{{ $href }}" target="_blank"
                style="display:inline-block; padding:12px 22px; border-radius:10px; font-family:Inter, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif; font-size:15px; font-weight:600; line-height:1.2; text-decoration:none; {{ $style }}">{{ $slot }}</a>
        </td>
    </tr>
</table>
