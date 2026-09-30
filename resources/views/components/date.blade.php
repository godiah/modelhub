@props(['date', 'format' => 'M j, Y', 'fallback' => '—'])
{{ $date ? \Illuminate\Support\Carbon::parse($date)->format($format) : $fallback }}
