@php $field = 'block w-28 rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm tabular-nums focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25'; @endphp
<x-staff-layout :title="__('Security settings')">
    <div class="container mx-auto max-w-3xl space-y-6 px-4 py-8">
        <div>
            <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ __('Platform settings') }}</h1>
            <p class="mt-1 text-sm text-tertiary">{{ __('Rules for the whole platform. Only Super admins see this page, and every change is recorded in the activity log.') }}</p>
        </div>

        <nav aria-label="{{ __('Settings') }}" class="flex gap-1 border-b border-neutral-200">
            <a href="{{ route('admin.settings.security') }}" aria-current="page" class="-mb-px border-b-2 border-secondary px-3 py-2 text-sm font-medium text-neutral-900">{{ __('Security') }}</a>
        </nav>

        <form method="POST" action="{{ route('admin.settings.security.update') }}" class="space-y-6">
            @csrf @method('PATCH')

            @foreach ($sections as $section)
                <x-panel :title="__($section['title'])" :description="__($section['description'])">
                    <div class="divide-y divide-neutral-100">
                        @foreach ($section['keys'] as $key)
                            @php $definition = $definitions[$key]; $name = str($key)->after('security.')->toString(); @endphp
                            @if ($definition['type'] === 'bool')
                                <x-switch :name="'settings['.$name.']'" :id="'setting-'.$name" :label="__($definition['label'])" :help="isset($definition['help']) ? __($definition['help']) : null" :checked="(bool) old('settings.'.$name, $values[$key])" />
                            @else
                                <div class="flex items-start justify-between gap-4 py-3.5">
                                    <label for="setting-{{ $name }}" class="min-w-0">
                                        <span class="block text-sm font-medium text-neutral-900">{{ __($definition['label']) }}</span>
                                        @isset($definition['help'])<span class="mt-0.5 block text-sm text-tertiary">{{ __($definition['help']) }}</span>@endisset
                                        @error('settings.'.$name)<span class="mt-1 block text-sm text-red-600" role="alert">{{ $message }}</span>@enderror
                                    </label>
                                    <span class="flex shrink-0 items-center gap-2">
                                        <input id="setting-{{ $name }}" type="number" inputmode="numeric" name="settings[{{ $name }}]" value="{{ old('settings.'.$name, $values[$key]) }}" min="{{ $definition['min'] }}" max="{{ $definition['max'] }}" required class="{{ $field }}">
                                        <span class="w-24 text-xs text-tertiary">{{ __($definition['unit']) }}</span>
                                    </span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </x-panel>
            @endforeach

            <div class="flex justify-end"><x-btn type="submit">{{ __('Save settings') }}</x-btn></div>
        </form>
    </div>
</x-staff-layout>
