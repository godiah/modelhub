{{--
    Guest shell for pages written with <x-app-layout> (browse, apply, ...): the same site header and footer
    as the landing page. Livewire's assets are loaded so Alpine-based pages keep working. Shares $slot,
    $title (tab title only) and $toolbar with layouts.site.
--}}
@include('layouts.site', ['alpine' => true])
