@props(['status'])

@php
    $friendlyStatuses = [
        'two-factor-code-sent' => 'Verification code sent to your email address.',
        'verification-link-sent' => 'A new verification link has been sent to the email address you provided during registration.',
    ];
@endphp

@if (isset($friendlyStatuses[$status]))
    <div class="rounded-md bg-green-50 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <x-icon name="check-circle-solid" class="h-5 w-5 text-green-400" />
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-green-800">
                    {{ $friendlyStatuses[$status] }}
                </p>
            </div>
        </div>
    </div>
@elseif ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-green-600']) }}>
        {{ $status }}
    </div>
@endif
