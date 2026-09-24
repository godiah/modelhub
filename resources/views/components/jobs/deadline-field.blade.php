@props([
    'checked' => false,
    'displayText' => 'Select Deadline',
    'dateValue' => null,
    'required' => true,
    'showFallbackInput' => false,
    'dimmed' => false,
])

<div {{ $attributes->merge(['class' => 'mb-2']) }}>
    <div class="flex items-center justify-between mb-2">
        <label for="deadline"
            class="text-sm font-medium text-primary {{ $required ? "after:content-['*'] after:ml-1 after:text-red-500" : '' }} font-tertiary">
            Deadline
        </label>
        <div class="flex items-center">
            @if ($showFallbackInput)
                <input type="hidden" name="no_deadline" value="0">
            @endif
            <input type="checkbox" id="no_deadline" name="no_deadline" value="1"
                class="h-4 w-4 text-secondary focus:ring-secondary border-neutral-300 rounded mr-2"
                {{ $checked ? 'checked' : '' }}>
            <label for="no_deadline" class="text-xs text-neutral-600">No Fixed Deadline</label>
        </div>
    </div>
    @error('deadline')
        <p class="text-red-500 text-xs italic">{{ $message }}</p>
    @enderror
    <div class="relative">
        <div id="deadline-display"
            class="w-full px-4 py-3 border border-neutral-300 rounded-lg cursor-pointer flex items-center justify-between transition-all duration-300 hover:border-secondary focus:ring-2 focus:ring-secondary/50 bg-white {{ $dimmed ? 'opacity-50' : '' }}">
            <span id="selected-date-text" class="text-neutral-600 text-sm">
                {{ $displayText }}
            </span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-secondary" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
        </div>

        <input type="date" id="deadline" name="deadline" class="absolute inset-0 opacity-0 cursor-pointer"
            value="{{ $dateValue }}">

        <div id="calendar-container"
            class="absolute z-50 mt-2 bg-white border border-neutral-300 rounded-lg shadow-lg p-4 hidden">
            <div id="calendar" class="grid grid-cols-7 gap-2 text-center"></div>
        </div>
    </div>
    <p class="text-xs text-tertiary mt-2 flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-secondary" fill="none"
            viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Specify when you need this project completed
    </p>
</div>
