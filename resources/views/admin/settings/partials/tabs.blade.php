<nav aria-label="{{ __('Settings') }}" class="flex gap-1 border-b border-neutral-200">
    @foreach ($groups as $key => [$class, $prefix, $title])
        <a href="{{ route('admin.settings.'.$key) }}" @if ($key === $group) aria-current="page" @endif
            @class(['-mb-px border-b-2 px-3 py-2 text-sm font-medium transition-colors', 'border-secondary text-neutral-900' => $key === $group, 'border-transparent text-neutral-500 hover:text-neutral-900' => $key !== $group])>{{ __($title) }}</a>
    @endforeach
</nav>
