<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-tertiary font-bold text-2xl text-primary leading-tight flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="h-6 w-6 mr-2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
                {{ __('Profile') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4">
                <livewire:profile.update-profile-information-form />
            </div>

            <div class="p-4">
                <livewire:profile.update-password-form />
            </div>

            <div class="p-4">
                <livewire:profile.user-profile-form />
            </div>

            <div class="p-4">
                <livewire:profile.social-links-form />
            </div>

            <div class="p-4">
                <livewire:profile.two-factor-form />
            </div>

            <div class="p-4">
                <livewire:profile.delete-user-form />
            </div>
        </div>
    </div>
</x-app-layout>
