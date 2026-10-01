@props(['roles', 'held' => []])
<fieldset>
    <legend class="sr-only">{{ __('Roles') }}</legend>
    <ul class="space-y-3">
        @foreach ($roles as $role)
            <li>
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-neutral-200 p-3.5 transition-colors hover:bg-neutral-50 has-[:checked]:border-teal-600 has-[:checked]:bg-teal-50/40">
                    <input type="checkbox" name="roles[]" value="{{ $role->name }}" @checked(in_array($role->name, $held, true)) class="mt-0.5 h-4 w-4 rounded border-neutral-300 text-teal-600 focus:ring-teal-600/30">
                    <span class="min-w-0"><span class="block text-sm font-semibold text-neutral-900">{{ $role->name }}</span>@if ($role->description)<span class="block text-xs text-tertiary">{{ $role->description }}</span>@endif</span>
                </label>
            </li>
        @endforeach
    </ul>
</fieldset>
