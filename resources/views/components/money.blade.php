@props(['amount', 'decimals' => 2])
{{ \App\Support\Money::format($amount, $decimals) }}
