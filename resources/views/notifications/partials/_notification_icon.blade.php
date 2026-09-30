{{-- This partial displays the appropriate icon for each notification's semantic category --}}
@php
    $presented = \App\Helpers\NotificationPresenterHelper::present($notification);
@endphp
@switch($presented['icon'])
    @case('message')
        <div class="p-2 bg-secondary/10 text-secondary rounded-lg">
            <x-icon name="envelope" class="h-5 w-5" />
        </div>
    @break

    @case('success')
        <div class="p-2 bg-green-100 text-green-600 rounded-lg">
            <x-icon name="check-circle" class="h-5 w-5" />
        </div>
    @break

    @case('danger')
        <div class="p-2 bg-red-100 text-red-600 rounded-lg">
            <x-icon name="exclamation-triangle-3" class="h-5 w-5" stroke-width="2" />
        </div>
    @break

    @default
        <div class="p-2 bg-primary/10 text-primary rounded-lg">
            <x-icon name="bell" class="h-5 w-5" />
        </div>
@endswitch
