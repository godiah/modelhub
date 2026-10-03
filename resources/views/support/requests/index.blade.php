@php
    use App\Support\SupportChat\PreviewTickets as Tickets;

    $all = collect(Tickets::memberList());
    $open = $all->where('status', '!=', 'resolved')->values();
    $resolved = $all->where('status', 'resolved')->values();
    $tab = request('tab', 'open') === 'resolved' ? 'resolved' : 'open';
    // ?state=empty shows what a member with no requests sees
    $rows = request('state') === 'empty' ? collect() : ($tab === 'resolved' ? $resolved : $open);
    $tabs = ['open' => [__('Open'), request('state') === 'empty' ? 0 : $open->count()], 'resolved' => [__('Resolved'), request('state') === 'empty' ? 0 : $resolved->count()]];
@endphp

<x-app-layout>
    <div class="container mx-auto max-w-4xl px-4 py-8">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="font-tertiary text-3xl font-bold tracking-tight text-neutral-900">{{ __('Your requests') }}</h2>
                <p class="mt-1 text-neutral-600">{{ __('Questions you have sent to ModelHub staff, and their replies.') }}</p>
            </div>
            <x-btn type="button" @click="$dispatch('support-open')"><x-icon name="chat-bubble-text" class="h-4 w-4" />{{ __('Ask for help') }}</x-btn>
        </header>

        <nav aria-label="{{ __('Filter requests') }}" class="mt-6">
            <ul class="flex items-center gap-2">
                @foreach ($tabs as $key => [$label, $count])
                    <li><a href="{{ request()->fullUrlWithQuery(['tab' => $key]) }}" @if ($tab === $key) aria-current="true" @endif
                        @class(['inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-4 focus-visible:ring-secondary/30', 'border-teal-600 bg-teal-600 text-white' => $tab === $key, 'border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50' => $tab !== $key])>
                        {{ $label }}<span @class(['text-xs tabular-nums', 'text-teal-100' => $tab === $key, 'text-neutral-500' => $tab !== $key])>{{ $count }}</span></a></li>
                @endforeach
            </ul>
        </nav>

        <div class="mt-5">
            @if ($rows->isEmpty())
                <x-empty-state icon="inbox" :title="$tab === 'resolved' ? __('Nothing resolved yet') : __('No requests yet')"
                    :description="__('When you ask staff for help, from the Help button or the contact form, it shows up here with their replies.')">
                    <x-btn type="button" @click="$dispatch('support-open')">{{ __('Ask for help') }}</x-btn>
                </x-empty-state>
            @else
                <ul class="divide-y divide-neutral-100 overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
                    @foreach ($rows as $t)
                        @php $status = Tickets::memberStatus($t['status']); @endphp
                        <li>
                            <a href="{{ route('dev.support.request', $t['ref']) }}" wire:navigate
                                class="flex items-start gap-4 px-5 py-4 transition-colors hover:bg-neutral-50 focus:outline-none focus-visible:bg-neutral-50 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-secondary/40">
                                <span aria-hidden="true" @class(['mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full', 'bg-teal-600' => $t['unread'], 'bg-transparent' => ! $t['unread']])></span>
                                <div class="min-w-0 flex-1">
                                    <p class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                        <span @class(['font-secondary text-[15px] text-neutral-900', 'font-bold' => $t['unread'], 'font-semibold' => ! $t['unread']])>{{ $t['category'] }}</span>
                                        @if ($t['unread'])<span class="rounded-full bg-teal-600 px-2 py-0.5 text-xs font-medium text-white">{{ __('New reply') }}</span>@endif
                                    </p>
                                    <p class="mt-0.5 truncate text-sm text-neutral-600">{{ $t['summary'] }}</p>
                                    <p class="mt-1 text-xs text-neutral-500"><span class="font-medium">{{ $t['ref'] }}</span>@if ($t['entities']) · {{ $t['entities'][0]['label'] }}@endif · {{ __('Updated') }} {{ $t['updated'] }}</p>
                                </div>
                                <x-badge :tone="$status['tone']" class="shrink-0 px-2.5 py-0.5 text-xs font-medium">{{ __($status['label']) }}</x-badge>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <p class="mt-6 text-sm text-neutral-500">{{ __('Staff reply here and by email. You can also reach us without signing in from the') }} <a href="{{ route('dev.support.contact') }}" class="font-medium text-teal-700 hover:underline">{{ __('contact form') }}</a>.</p>
    </div>
</x-app-layout>
