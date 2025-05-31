<?php

use App\Models\SocialNetwork;
use App\Models\UserSocialLink;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component {
    public array $social_links = [];
    public bool $showAddForm = false;
    public int $editingId = 0;

    // Form fields
    public int $social_network_id = 0;
    public string $username = '';
    public string $url = '';
    public string $display_name = '';
    public bool $is_public = true;

    /**
     * Mount the component with existing social links.
     */
    public function mount(): void
    {
        $this->loadSocialLinks();
    }

    /**
     * Load user's social links.
     */
    public function loadSocialLinks(): void
    {
        $this->social_links = Auth::user()->socialLinks()->with('socialNetwork')->ordered()->get()->toArray();
    }

    /**
     * Get all active social networks for the select dropdown.
     */
    public function getSocialNetworksProperty()
    {
        return SocialNetwork::active()->ordered()->get();
    }

    /**
     * Show add form.
     */
    public function showAdd(): void
    {
        $this->resetForm();
        $this->showAddForm = true;
    }

    /**
     * Cancel add/edit form.
     */
    public function cancel(): void
    {
        $this->resetForm();
        $this->showAddForm = false;
        $this->editingId = 0;
    }

    /**
     * Edit existing social link.
     */
    public function edit(int $id): void
    {
        $socialLink = Auth::user()->socialLinks()->findOrFail($id);

        $this->editingId = $id;
        $this->social_network_id = $socialLink->social_network_id;
        $this->username = $socialLink->username ?? '';
        $this->url = $socialLink->url;
        $this->display_name = $socialLink->display_name ?? '';
        $this->is_public = $socialLink->is_public;
        $this->showAddForm = true;
    }

    /**
     * Save social link (create or update).
     */
    public function save(): void
    {
        try {
            $validated = $this->validate([
                'social_network_id' => 'required|exists:social_networks,id',
                'username' => 'nullable|string|max:255',
                'url' => 'required|url|max:500',
                'display_name' => 'nullable|string|max:255',
                'is_public' => 'boolean',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }

        $user = Auth::user();

        if ($this->editingId) {
            // Update existing
            $socialLink = $user->socialLinks()->findOrFail($this->editingId);
            $socialLink->update($validated);
        } else {
            // Create new
            $validated['user_id'] = $user->id;
            $validated['sort_order'] = $user->socialLinks()->max('sort_order') + 1;
            UserSocialLink::create($validated);
        }

        $this->loadSocialLinks();
        $this->cancel();
        $this->dispatch('social-links-updated');
    }

    /**
     * Delete social link.
     */
    public function delete(int $id): void
    {
        Auth::user()->socialLinks()->findOrFail($id)->delete();
        $this->loadSocialLinks();
        $this->dispatch('social-links-updated');
    }

    /**
     * Move social link up in order.
     */
    public function moveUp(int $id): void
    {
        $this->reorderSocialLink($id, 'up');
    }

    /**
     * Move social link down in order.
     */
    public function moveDown(int $id): void
    {
        $this->reorderSocialLink($id, 'down');
    }

    /**
     * Reorder social links.
     */
    private function reorderSocialLink(int $id, string $direction): void
    {
        $user = Auth::user();
        $socialLinks = $user->socialLinks()->ordered()->get();

        $currentIndex = $socialLinks->search(function ($link) use ($id) {
            return $link->id === $id;
        });

        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        if ($targetIndex < 0 || $targetIndex >= $socialLinks->count()) {
            return;
        }

        // Swap sort orders
        $currentLink = $socialLinks[$currentIndex];
        $targetLink = $socialLinks[$targetIndex];

        $tempOrder = $currentLink->sort_order;
        $currentLink->update(['sort_order' => $targetLink->sort_order]);
        $targetLink->update(['sort_order' => $tempOrder]);

        $this->loadSocialLinks();
    }

    /**
     * Auto-generate URL from username and social network.
     */
    public function updatedUsername(): void
    {
        if ($this->username && $this->social_network_id) {
            $socialNetwork = SocialNetwork::find($this->social_network_id);
            if ($socialNetwork && $socialNetwork->base_url) {
                $this->url = $socialNetwork->generateUrl($this->username);
            }
        }
    }

    /**
     * Reset form fields.
     */
    private function resetForm(): void
    {
        $this->social_network_id = 0;
        $this->username = '';
        $this->url = '';
        $this->display_name = '';
        $this->is_public = true;
    }
}; ?>

<section class="bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden">
    <!-- Header Section -->
    <div class="bg-gradient-to-r from-primary to-primary/90 px-6 py-5">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1">
                        </path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-white font-secondary">
                        {{ __('Social Links') }}
                    </h2>
                    <p class="text-white/60 text-sm font-main mt-1">
                        {{ __('Manage your social media profiles and website links') }}
                    </p>
                </div>
            </div>

            @if (!$showAddForm)
                <button wire:click="showAdd"
                    class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-primary font-main">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    {{ __('Add Link') }}
                </button>
            @endif
        </div>
    </div>

    <!-- Content Section -->
    <div class="p-6">
        <!-- Add/Edit Form -->
        @if ($showAddForm)
            <div class="bg-secondary/5 border border-secondary/20 rounded-lg p-6 mb-8">
                <div class="flex items-start space-x-3 mb-6">
                    <div class="w-8 h-8 bg-secondary/20 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-neutral-900 font-main">
                            {{ $editingId ? __('Edit Social Link') : __('Add New Social Link') }}
                        </h3>
                        <p class="text-sm text-neutral-600 font-main mt-1">
                            {{ __('Connect your social media profiles and professional links') }}
                        </p>
                    </div>
                </div>

                <form wire:submit="save" class="space-y-6">
                    <!-- Platform and Username Row -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Social Network Selection -->
                        <div class="space-y-2">
                            <label for="social_network_id" class="block text-sm font-medium text-neutral-700 font-main">
                                {{ __('Platform') }}
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-neutral-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9"></path>
                                    </svg>
                                </div>
                                <select wire:model.live="social_network_id" id="social_network_id"
                                    class="text-sm block w-full pl-10 pr-3 py-3 border border-neutral-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-colors duration-200 font-main">
                                    <option value="0">{{ __('Select Platform') }}</option>
                                    @foreach ($this->socialNetworks as $network)
                                        <option value="{{ $network->id }}">{{ $network->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('social_network_id')
                                <div class="flex items-center space-x-2 text-red-600 text-sm font-main">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    <span>{{ $message }}</span>
                                </div>
                            @enderror
                        </div>

                        <!-- Username -->
                        <div class="space-y-2">
                            <label for="username" class="block text-sm font-medium text-neutral-700 font-main">
                                {{ __('Username') }} <span
                                    class="text-neutral-500 font-normal">({{ __('Optional') }})</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-neutral-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                        </path>
                                    </svg>
                                </div>
                                <input type="text" wire:model.live="username" id="username"
                                    class="text-sm block w-full pl-10 pr-3 py-3 border border-neutral-300 rounded-lg shadow-sm placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-colors duration-200 font-main"
                                    placeholder="{{ __('your_username') }}">
                            </div>
                            <p class="text-xs text-neutral-500 font-main">
                                {{ __('Will auto-generate URL for known platforms') }}</p>
                            @error('username')
                                <div class="flex items-center space-x-2 text-red-600 text-sm font-main">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    <span>{{ $message }}</span>
                                </div>
                            @enderror
                        </div>
                    </div>

                    <!-- URL -->
                    <div class="space-y-2">
                        <label for="url" class="block text-sm font-medium text-neutral-700 font-main">
                            {{ __('URL') }}
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-neutral-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1">
                                    </path>
                                </svg>
                            </div>
                            <input type="url" wire:model="url" id="url"
                                class="text-sm block w-full pl-10 pr-3 py-3 border border-neutral-300 rounded-lg shadow-sm placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-colors duration-200 font-main"
                                placeholder="{{ __('https://example.com/profile') }}" required>
                        </div>
                        @error('url')
                            <div class="flex items-center space-x-2 text-red-600 text-sm font-main">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd"></path>
                                </svg>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror
                    </div>

                    <!-- Display Name and Public Toggle Row -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Display Name -->
                        <div class="space-y-2">
                            <label for="display_name" class="block text-sm font-medium text-neutral-700 font-main">
                                {{ __('Display Name') }} <span
                                    class="text-neutral-500 font-normal">({{ __('Optional') }})</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-neutral-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z">
                                        </path>
                                    </svg>
                                </div>
                                <input type="text" wire:model="display_name" id="display_name"
                                    class="text-sm block w-full pl-10 pr-3 py-3 border border-neutral-300 rounded-lg shadow-sm placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-colors duration-200 font-main"
                                    placeholder="{{ __('Custom display name') }}">
                            </div>
                            @error('display_name')
                                <div class="flex items-center space-x-2 text-red-600 text-sm font-main">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    <span>{{ $message }}</span>
                                </div>
                            @enderror
                        </div>

                        <!-- Public Toggle -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-neutral-700 font-main">
                                {{ __('Visibility') }}
                            </label>
                            <div class="flex items-center space-x-3 pt-3">
                                <div class="relative inline-flex items-center">
                                    <input type="checkbox" wire:model="is_public" id="is_public"
                                        class="sr-only peer">
                                    <div
                                        class="w-11 h-6 bg-neutral-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-secondary/25 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-secondary">
                                    </div>
                                </div>
                                <label for="is_public" class="text-sm text-neutral-700 font-main cursor-pointer">
                                    {{ __('Show publicly on profile') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-end space-x-3 pt-6 border-t border-neutral-200 text-sm">
                        <button type="button" wire:click="cancel"
                            class="inline-flex items-center px-6 py-3 bg-neutral-200 hover:bg-neutral-300 text-neutral-700 font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-neutral-500 focus:ring-offset-2 font-main">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit"
                            class="inline-flex items-center px-6 py-3 bg-secondary hover:bg-secondary/90 text-white font-medium rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2 font-main">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                            {{ $editingId ? __('Update Link') : __('Add Link') }}
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Social Links List -->
        @if (empty($social_links))
            <div class="text-center py-12">
                <div class="w-16 h-16 mx-auto bg-neutral-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1">
                        </path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-neutral-900 font-main mb-2">{{ __('No social links yet') }}</h3>
                <p class="text-neutral-600 font-main mb-6">
                    {{ __('Connect your social media profiles and professional links to showcase your online presence.') }}
                </p>
                @if (!$showAddForm)
                    <button wire:click="showAdd"
                        class="inline-flex items-center px-6 py-3 bg-secondary hover:bg-secondary/90 text-white font-medium rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2 font-main">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                            </path>
                        </svg>
                        {{ __('Add Your First Link') }}
                    </button>
                @endif
            </div>
        @else
            <div class="space-y-4">
                @foreach ($social_links as $index => $link)
                    <div
                        class="group bg-neutral-50 hover:bg-neutral-100 border border-neutral-200 rounded-lg p-4 transition-all duration-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4 flex-1 min-w-0">
                                <!-- Social Network Icon -->
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center shadow-sm"
                                        style="background: linear-gradient(135deg, {{ $link['social_network']['color'] }}20, {{ $link['social_network']['color'] }}10);">
                                        <i class="{{ $link['social_network']['icon'] }} text-xl"
                                            style="color: {{ $link['social_network']['color'] }};"></i>
                                    </div>
                                </div>

                                <!-- Link Details -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-3 mb-1">
                                        <h4 class="text-base font-semibold text-neutral-900 font-main">
                                            {{ $link['social_network']['name'] }}
                                        </h4>
                                        @if (!$link['is_public'])
                                            <span
                                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-neutral-200 text-neutral-700 font-main">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21">
                                                    </path>
                                                </svg>
                                                {{ __('Private') }}
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-secondary/20 text-secondary font-main">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                    </path>
                                                </svg>
                                                {{ __('Public') }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-neutral-600 font-main mb-1">
                                        {{ $link['display_name'] ?: ($link['username'] ?: __('Custom Link')) }}
                                    </p>
                                    <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer"
                                        class="text-sm text-secondary hover:text-secondary/80 font-main truncate block transition-colors duration-200">
                                        {{ $link['url'] }}
                                        <svg class="w-3 h-3 inline ml-1" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14">
                                            </path>
                                        </svg>
                                    </a>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div
                                class="flex items-center space-x-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                <!-- Reorder Buttons -->
                                @if ($index > 0)
                                    <button wire:click="moveUp({{ $link['id'] }})"
                                        class="p-2 text-neutral-400 hover:text-neutral-600 hover:bg-white rounded-lg transition-all duration-200"
                                        title="{{ __('Move up') }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 15l7-7 7 7"></path>
                                        </svg>
                                    </button>
                                @endif

                                @if ($index < count($social_links) - 1)
                                    <button wire:click="moveDown({{ $link['id'] }})"
                                        class="p-2 text-neutral-400 hover:text-neutral-600 hover:bg-white rounded-lg transition-all duration-200"
                                        title="{{ __('Move down') }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                @endif

                                <!-- Edit Button -->
                                <button wire:click="edit({{ $link['id'] }})"
                                    class="p-2 text-secondary hover:text-secondary/80 hover:bg-secondary/10 rounded-lg transition-all duration-200"
                                    title="{{ __('Edit') }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                        </path>
                                    </svg>
                                </button>

                                <!-- Delete Button -->
                                <button wire:click="delete({{ $link['id'] }})"
                                    wire:confirm="{{ __('Are you sure you want to delete this social link?') }}"
                                    class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-all duration-200"
                                    title="{{ __('Delete') }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Success Message -->
    <div x-data="{ show: false }" x-show="show" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform translate-x-full"
        x-transition:enter-end="opacity-100 transform translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform translate-x-0"
        x-transition:leave-end="opacity-0 transform translate-x-full"
        @social-links-updated.window="show = true; setTimeout(() => show = false, 4000)"
        class="fixed top-4 right-4 bg-secondary/10 border border-secondary/30 text-secondary px-6 py-4 rounded-lg shadow-lg z-50"
        style="display: none;">
        <div class="flex items-center space-x-3">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd"></path>
            </svg>
            <span class="font-medium font-main">{{ __('Social links updated successfully!') }}</span>
        </div>
    </div>
</section>
