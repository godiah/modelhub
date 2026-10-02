{{-- One group of settings as a card. Needs: $section, $definitions, $values, $prefix. --}}
<x-panel :title="__($section['title'])" :description="__($section['description'])">
                    <div class="divide-y divide-neutral-100">
                        @foreach ($section['keys'] as $key)
                            @php $definition = $definitions[$key]; $name = str($key)->after($prefix.'.')->toString(); @endphp
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
                                        <input id="setting-{{ $name }}" type="number" inputmode="{{ $definition['type'] === 'float' ? 'decimal' : 'numeric' }}" step="{{ $definition['type'] === 'float' ? '0.01' : '1' }}" name="settings[{{ $name }}]" value="{{ old('settings.'.$name, $values[$key]) }}" min="{{ $definition['min'] }}" max="{{ $definition['max'] }}" required class="{{ $field }}">
                                        <span class="w-24 text-xs text-tertiary">{{ __($definition['unit']) }}</span>
                                    </span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </x-panel>
