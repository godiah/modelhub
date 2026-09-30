@props(['value' => null, 'required' => false, 'step' => null])

<div {{ $attributes->merge(['class' => 'mb-2']) }}>
    <label for="budget"
        class="block text-sm font-medium text-primary mb-2 {{ $required ? "after:content-['*'] after:ml-1 after:text-red-500" : '' }} font-tertiary">
        Budget
    </label>
    <x-form.error name="budget" variant="plain" />
    <div class="relative">
        <div
            class="absolute inset-y-0 left-0 flex items-center pr-3 pl-3 pointer-events-none bg-neutral-100 rounded-l-lg border-r border-neutral-200">
            <span class="text-neutral-600 font-medium">Ksh.</span>
        </div>
        <input type="number" id="budget" name="budget" min="0" @if ($required) required @endif
            @if ($step) step="{{ $step }}" @endif placeholder="Enter project budget"
            class="w-full pl-16 px-4 py-3 border border-neutral-300 rounded-lg text-sm focus:ring-2 focus:ring-secondary/50 focus:border-secondary transition-all duration-200 font-secondary appearance-none"
            value="{{ $value }}">
        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
            <x-icon name="coins" class="h-5 w-5 text-neutral-400" fill="#14b8a6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        </div>
    </div>
    <p class="text-xs text-tertiary mt-2 flex items-center">
        <x-icon name="information-circle" class="h-4 w-4 mr-1 text-secondary" />
        Enter budget in Kenyan Shillings
    </p>
</div>
