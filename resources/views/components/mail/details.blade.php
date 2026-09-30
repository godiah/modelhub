@props(['title' => null])

{{-- A quiet box of label / value rows. Fill it with <x-mail.detail label="…">value</x-mail.detail>. --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
    style="margin:0 0 20px; background-color:#F9FAFB; border:1px solid #E5E7EB; border-radius:12px;">
    @if ($title)
        <tr>
            <td colspan="2" style="padding:14px 18px 6px; font-family:'Plus Jakarta Sans', Inter, Arial, sans-serif; font-size:12px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#6B7280;">{{ $title }}</td>
        </tr>
    @endif
    {{ $slot }}
    <tr><td colspan="2" style="height:6px; line-height:6px; font-size:0;">&nbsp;</td></tr>
</table>
