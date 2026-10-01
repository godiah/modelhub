<section aria-labelledby="activity-heading">
    <x-card clip>
        <div class="flex items-center justify-between gap-3 border-b border-neutral-100 px-5 py-4">
            <h2 id="activity-heading" class="font-tertiary text-base font-semibold text-neutral-900">{{ __('Recent staff activity') }}</h2>
            <a href="{{ route('admin.activity.index') }}" wire:navigate class="text-sm font-medium text-teal-700 hover:text-teal-800">{{ __('Open log') }}</a>
        </div>
        @if ($activity->isEmpty())
            <p class="px-5 py-8 text-center text-sm text-tertiary">{{ __('Nothing recorded yet.') }}</p>
        @else
            <ul class="divide-y divide-neutral-100">
                @foreach ($activity as $entry)
                    <li class="flex items-start gap-3 px-5 py-3">
                        @if ($entry->staff)<x-user-avatar :user="$entry->staff" size="h-7 w-7" />@else<span class="h-7 w-7 shrink-0 rounded-full bg-neutral-100"></span>@endif
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-neutral-800"><span class="font-semibold text-neutral-900">{{ $entry->staff?->name ?? __('Someone') }}</span> {{ \Illuminate\Support\Str::limit(\Illuminate\Support\Str::lcfirst($entry->summary), 90) }}</p>
                            <p class="mt-0.5 text-xs text-tertiary">{{ $entry->created_at->diffForHumans() }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>
</section>
