<x-app-layout crumb="Archived">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <!-- Header -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ __('Archived applications') }}</h1>
                <p class="mt-1 max-w-2xl text-sm text-tertiary">{{ __('Finished applications you have put away. Restore one to see it in My applications again.') }}</p>
            </div>
            <x-btn variant="secondary" size="sm" href="{{ route('applications.my') }}" class="shrink-0">
                <x-icon name="arrow-left" class="h-4 w-4" />
                {{ __('My applications') }}
            </x-btn>
        </div>

        @if ($applications->isEmpty())
            <x-empty-state icon="archive-box" :title="__('Nothing archived')"
                :description="__('Once an application is finished (rejected, withdrawn, filled by someone else or completed) you can archive it from My applications.')">
                <x-btn href="{{ route('applications.my') }}">{{ __('Go to My applications') }}</x-btn>
            </x-empty-state>
        @else
            <div class="space-y-3">
                @foreach ($applications as $application)
                    <x-applications.row :application="$application" :archived="true" />
                @endforeach
            </div>

            <x-pager :paginator="$applications" />
        @endif
    </div>
</x-app-layout>
