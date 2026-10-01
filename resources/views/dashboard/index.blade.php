<x-app-layout>
    @php
        $user = auth()->user();
        $needsAction = $overview['summary']['needs_action'];
    @endphp

    <div class="container mx-auto max-w-7xl px-4 py-8">
        <!-- Greeting -->
        <div class="mb-6">
            <h2 class="font-tertiary text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl">
                {{ __('Welcome back, :name', ['name' => \Illuminate\Support\Str::before($user->name, ' ')]) }}
            </h2>
            <p class="mt-1 text-sm text-tertiary sm:text-base">
                @if ($needsAction > 0)
                    <a href="#attention" class="rounded underline decoration-neutral-300 underline-offset-4 hover:text-teal-700 hover:decoration-teal-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">{{ trans_choice(':count thing needs your attention|:count things need your attention', $needsAction, ['count' => $needsAction]) }}</a>.
                @else
                    {{ __("You're all caught up.") }}
                @endif
            </p>
        </div>

        @include('dashboard.partials.summary', ['summary' => $overview['summary'], 'models' => $overview['models']])

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Main column -->
            <div class="space-y-6 lg:col-span-2">
                @include('dashboard.partials.attention', ['items' => $overview['attention'], 'more' => $overview['attentionMore']])
                @include('dashboard.partials.engagements', ['engagements' => $overview['engagements']])
                @if ($overview['models']['seller'])
                    @include('dashboard.partials.models', ['models' => $overview['models']['models'], 'store' => $overview['models']['store']])
                    @if ($overview['models']['reviews']->isNotEmpty())
                        @include('dashboard.partials.model-reviews', ['reviews' => $overview['models']['reviews']])
                    @endif
                @endif
                @if ($overview['reviews']->isNotEmpty())
                    @include('dashboard.partials.reviews', ['reviews' => $overview['reviews']])
                @endif
            </div>

            <!-- Side column -->
            <div class="space-y-6">
                @include('dashboard.partials.profile-card', ['profile' => $overview['profile']])
                @include('dashboard.partials.notifications', ['notifications' => $overview['notifications']])
                @include('dashboard.partials.applications', ['applications' => $overview['applications']])
                @include('dashboard.partials.projects', ['projects' => $overview['projects']])
            </div>
        </div>
    </div>
</x-app-layout>
