<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-tertiary font-bold text-2xl text-primary leading-tight flex items-center">
                <x-icon name="user-2" class="h-6 w-6 mr-2" />
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
