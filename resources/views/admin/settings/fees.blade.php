@php $field = 'block w-28 rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm tabular-nums focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25'; @endphp
<x-staff-layout :title="__('Fees and payments')">
    <div class="container mx-auto max-w-7xl space-y-6 px-4 py-8">
        <x-staff.header class="!mb-0" :title="__('Platform settings')">{{ __('Rules for the whole platform. Only Super admins see this page, and every change is recorded in the activity log.') }}</x-staff.header>

        @include('admin.settings.partials.tabs')

        <form method="POST" action="{{ route('admin.settings.fees.update') }}" class="space-y-6">
            @csrf @method('PATCH')

            <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-2">
                @foreach ($sections as $section)
                    @include('admin.settings.partials.section')
                @endforeach
            </div>

            <p class="rounded-xl bg-neutral-50 px-4 py-3 text-sm text-neutral-700">{{ __('Changing a rate never rewrites what has already been agreed: every job offer and every sale keeps the rate it was made at.') }}</p>

            <div class="flex justify-end"><x-btn type="submit">{{ __('Save settings') }}</x-btn></div>
        </form>
    </div>
</x-staff-layout>
