<x-staff-layout :title="__('Activity log')">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <x-staff.header :title="__('Activity log')">{{ __('Who did what in the staff portal, and when: decisions on models, sellers, reviews and disputes, changes to staff and roles, and sign-ins. Entries are never edited or deleted.') }}</x-staff.header>

        <form method="GET" action="{{ route('admin.activity.index') }}" class="mb-5 flex flex-wrap items-end gap-3">
            <div>
                <label for="area" class="mb-1 block text-xs font-medium text-tertiary">{{ __('Area') }}</label>
                <select id="area" name="area" onchange="this.form.requestSubmit()" class="rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25">
                    <option value="">{{ __('Everything') }}</option>
                    @foreach ($areas as $key => $label)<option value="{{ $key }}" @selected($area === $key)>{{ __($label) }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="staff" class="mb-1 block text-xs font-medium text-tertiary">{{ __('Person') }}</label>
                <select id="staff" name="staff" onchange="this.form.requestSubmit()" class="rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25">
                    <option value="">{{ __('Anyone') }}</option>
                    @foreach ($people as $person)<option value="{{ $person->id }}" @selected($staffId === $person->id)>{{ $person->name }}</option>@endforeach
                </select>
            </div>
            @if ($area || $staffId)<a href="{{ route('admin.activity.index') }}" class="pb-2 text-sm font-medium text-teal-700 hover:underline">{{ __('Clear') }}</a>@endif
        </form>

        @if ($entries->isEmpty())
            <x-empty-state icon="document-text" :title="__('Nothing here')" :description="__('No activity matches this filter.')" />
        @else
            <x-card clip>
                <ul class="divide-y divide-neutral-100">
                    @foreach ($entries as $entry)
                        <li class="flex items-start gap-3 px-5 py-3.5">
                            @if ($entry->staff)<x-user-avatar :user="$entry->staff" size="h-9 w-9" />@else<span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-neutral-100 text-neutral-400"><x-icon name="user" class="h-5 w-5" /></span>@endif
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-neutral-900"><span class="font-medium">{{ $entry->staff?->name ?? __('Someone') }}</span> <span class="text-neutral-700">{{ \Illuminate\Support\Str::lcfirst($entry->summary) }}</span></p>
                                @if (! empty($entry->details['reason'] ?? $entry->details['notes'] ?? null))<p class="mt-1 line-clamp-2 rounded-lg bg-neutral-50 px-3 py-1.5 text-xs text-neutral-600">{{ $entry->details['reason'] ?? $entry->details['notes'] }}</p>@endif
                                <p class="mt-0.5 text-xs text-tertiary"><span class="font-mono">{{ $entry->action }}</span> · {{ $entry->created_at->format('M j, Y · g:i A') }}@if ($entry->ip_address) · {{ $entry->ip_address }}@endif</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-card>
            <x-pager :paginator="$entries" />
        @endif
    </div>
</x-staff-layout>
