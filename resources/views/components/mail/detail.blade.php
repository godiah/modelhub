@props(['label'])

<tr>
    <td valign="top" width="34%" style="padding:8px 8px 8px 18px; font-size:14px; color:#6B7280;">{{ $label }}</td>
    <td valign="top" style="padding:8px 18px 8px 0; font-size:14px; font-weight:500; color:#111827; word-break:break-word;">{{ $slot }}</td>
</tr>
