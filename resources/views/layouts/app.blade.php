{{--
    <x-app-layout> dispatcher. Pages stay layout-agnostic: signed-in users get the sidebar shell,
    guests get the public top-nav shell. @include shares $slot and $header with the chosen layout.
--}}
@include(auth()->check() ? 'layouts.dashboard' : 'layouts.public')
