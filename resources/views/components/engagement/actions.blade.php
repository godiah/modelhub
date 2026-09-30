@props(['engagement', 'summary', 'actions', 'current' => false])

{{--
    The engagement's primary action plus its "..." menu, shared by the list row and the workspace header.
    `current` = we are already on the workspace: menu/primary links to its tabs become tab switches
    (the workspace page defines Alpine's `show(tab)`), and "Open full page" is omitted.
    The page must include the cancellation modal for this engagement and the shared review/archive modals.
--}}
@php
    $status = $engagement->status;
    $primary = $actions['primary'];
    $itemClass = 'flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm text-neutral-700 transition-colors hover:bg-neutral-50 focus:bg-neutral-50 focus:outline-none';
    $reviewPayload = "{ name: 'review-engagement', id: {$engagement->id}, status: '{$status->value}' }";
@endphp

<div {{ $attributes->class('flex shrink-0 items-center gap-2') }} x-data="{ menu: false }" @click.outside="menu = false" @keydown.escape="menu = false">
    @switch($primary)
        @case('respond')
            <x-btn size="sm" href="{{ route('engagements.response-form', ['applicationId' => $engagement->application_id]) }}">{{ __('Respond to offer') }}</x-btn>
        @break

        @case('review')
        @case('revise')
            @if ($current)
                <x-btn size="sm" type="button" @click="show('deliverables')">{{ $primary === 'review' ? __('Review work') : __('Revise') }}</x-btn>
            @else
                <x-btn size="sm" href="{{ $actions['deliverables_url'] }}">{{ $primary === 'review' ? __('Review work') : __('Revise') }}</x-btn>
            @endif
        @break

        @case('settle')
            <x-btn size="sm" href="{{ $actions['settlement_url'] }}">{{ __('Settle') }}</x-btn>
        @break

        @case('dispute')
            <x-btn size="sm" variant="secondary" href="{{ $actions['dispute_url'] }}">{{ __('View dispute') }}</x-btn>
        @break

        @case('leave-review')
            <x-btn size="sm" type="button" @click="$dispatch('open-modal', {{ $reviewPayload }})">{{ __('Leave a review') }}</x-btn>
        @break
    @endswitch

    <!-- More actions -->
    <div class="relative">
        <button type="button" @click="menu = !menu" :aria-expanded="menu.toString()" aria-haspopup="menu"
            aria-label="{{ __('More actions') }}"
            class="rounded-lg p-2 text-neutral-500 transition-colors hover:bg-neutral-100 hover:text-neutral-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
            <x-icon name="ellipsis-vertical" class="h-5 w-5" />
        </button>

        <div x-show="menu" x-cloak x-transition.opacity role="menu"
            class="absolute right-0 z-30 mt-2 w-60 rounded-xl border border-neutral-200 bg-white p-1.5 shadow-lg">
            @unless ($current)
                <a href="{{ $actions['workspace_url'] }}" role="menuitem" class="{{ $itemClass }}">
                    <x-icon name="arrow-top-right-on-square" class="h-4 w-4 text-neutral-400" />{{ __('Open workspace') }}
                </a>
            @endunless

            @if ($actions['can_message'])
                @if ($current)
                    <button type="button" role="menuitem" class="{{ $itemClass }}" @click="menu = false; show('messages')">
                        <x-icon name="chat-bubble-left-right" class="h-4 w-4 text-neutral-400" />
                        <span class="flex-1">{{ __('Messages') }}</span>
                        @if ($summary['unread_messages'] > 0)
                            <span data-unread-for="{{ $engagement->id }}" class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-800">{{ $summary['unread_messages'] }}</span>
                        @endif
                    </button>
                @else
                    <a href="{{ $actions['messages_url'] }}" role="menuitem" class="{{ $itemClass }}">
                        <x-icon name="chat-bubble-left-right" class="h-4 w-4 text-neutral-400" />
                        <span class="flex-1">{{ __('Messages') }}</span>
                        @if ($summary['unread_messages'] > 0)
                            <span data-unread-for="{{ $engagement->id }}" class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-800">{{ $summary['unread_messages'] }}</span>
                        @endif
                    </a>
                @endif
            @endif

            @if ($actions['can_settle'] && $primary !== 'settle')
                <a href="{{ $actions['settlement_url'] }}" role="menuitem" class="{{ $itemClass }}">
                    <x-icon name="banknotes" class="h-4 w-4 text-neutral-400" />{{ __('Settlement') }}
                </a>
            @endif

            @if ($actions['is_finished'] && !$actions['reviewed'] && $primary !== 'leave-review')
                <button type="button" role="menuitem" class="{{ $itemClass }}"
                    @click="menu = false; $dispatch('open-modal', {{ $reviewPayload }})">
                    <x-icon name="star" class="h-4 w-4 text-neutral-400" />{{ __('Leave a review') }}
                </button>
            @endif

            @if ($actions['is_finished'] && $actions['reviewed'])
                <button type="button" role="menuitem" class="{{ $itemClass }}"
                    @click="menu = false; $dispatch('open-modal', { name: 'archive-engagement', id: {{ $engagement->id }} })">
                    <x-icon name="archive-box-3" class="h-4 w-4 text-neutral-400" />{{ __('Archive') }}
                </button>
            @endif

            @if ($actions['can_reopen'])
                <form action="{{ route('engagements.reopen-job', $engagement) }}" method="POST">
                    @csrf
                    <button type="submit" role="menuitem" class="{{ $itemClass }}">
                        <x-icon name="arrow-path" class="h-4 w-4 text-neutral-400" />{{ __('Reopen job') }}
                    </button>
                </form>
            @endif

            @if ($actions['can_cancel'])
                <div class="my-1.5 border-t border-neutral-100"></div>
                <button type="button" role="menuitem"
                    class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm text-red-600 transition-colors hover:bg-red-50 focus:bg-red-50 focus:outline-none"
                    @click="menu = false; $dispatch('open-modal', 'cancel-engagement-{{ $engagement->id }}')">
                    <x-icon name="x-mark" class="h-4 w-4" />{{ __('Cancel engagement') }}
                </button>
            @endif
        </div>
    </div>
</div>
