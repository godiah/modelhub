<?php

use App\Models\SocialNetwork;
use App\Models\UserSocialLink;
use Illuminate\Support\Facades\Auth;
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
        $validated = $this->validate([
            'social_network_id' => 'required|exists:social_networks,id',
            'username' => 'nullable|string|max:255',
            'url' => 'required|url|max:500',
            'display_name' => 'nullable|string|max:255',
            'is_public' => 'boolean',
        ]);

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
    <x-section-header :title="__('Social Links')"
        :subtitle="__('Manage your social media profiles and website links')">
        <x-slot:icon>
            <x-icon name="link" class="w-5 h-5 text-white" />
        </x-slot:icon>
        @if (!$showAddForm)
            <x-slot:action>
                <button wire:click="showAdd"
                    class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-primary font-main">
                    <x-icon name="plus" class="w-4 h-4 mr-2" />
                    {{ __('Add Link') }}
                </button>
            </x-slot:action>
        @endif
    </x-section-header>

    <!-- Content Section -->
    <div class="p-6">
        <!-- Add/Edit Form -->
        @if ($showAddForm)
            <div class="bg-secondary/5 border border-secondary/20 rounded-lg p-6 mb-8">
                <div class="flex items-start space-x-3 mb-6">
                    <div class="w-8 h-8 bg-secondary/20 rounded-lg flex items-center justify-center flex-shrink-0">
                        <x-icon name="plus" class="w-4 h-4 text-secondary" />
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
                            <x-form.label class="font-main" for="social_network_id">
                                {{ __('Platform') }}
                            </x-form.label>
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
                            <x-form.error name="social_network_id" />
                        </div>

                        <!-- Username -->
                        <div class="space-y-2">
                            <x-form.label class="font-main" for="username">
                                {{ __('Username') }} <span
                                    class="text-neutral-500 font-normal">({{ __('Optional') }})</span>
                            </x-form.label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <x-icon name="user" class="h-5 w-5 text-neutral-400" />
                                </div>
                                <input type="text" wire:model.live="username" id="username"
                                    class="text-sm block w-full pl-10 pr-3 py-3 border border-neutral-300 rounded-lg shadow-sm placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-colors duration-200 font-main"
                                    placeholder="{{ __('your_username') }}">
                            </div>
                            <p class="text-xs text-neutral-500 font-main">
                                {{ __('Will auto-generate URL for known platforms') }}</p>
                            <x-form.error name="username" />
                        </div>
                    </div>

                    <!-- URL -->
                    <div class="space-y-2">
                        <x-form.label class="font-main" for="url">
                            {{ __('URL') }}
                        </x-form.label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <x-icon name="link" class="h-5 w-5 text-neutral-400" />
                            </div>
                            <input type="url" wire:model="url" id="url"
                                class="text-sm block w-full pl-10 pr-3 py-3 border border-neutral-300 rounded-lg shadow-sm placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-colors duration-200 font-main"
                                placeholder="{{ __('https://example.com/profile') }}" required>
                        </div>
                        <x-form.error name="url" />
                    </div>

                    <!-- Display Name and Public Toggle Row -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Display Name -->
                        <div class="space-y-2">
                            <x-form.label class="font-main" for="display_name">
                                {{ __('Display Name') }} <span
                                    class="text-neutral-500 font-normal">({{ __('Optional') }})</span>
                            </x-form.label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <x-icon name="tag" class="h-5 w-5 text-neutral-400" />
                                </div>
                                <input type="text" wire:model="display_name" id="display_name"
                                    class="text-sm block w-full pl-10 pr-3 py-3 border border-neutral-300 rounded-lg shadow-sm placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-colors duration-200 font-main"
                                    placeholder="{{ __('Custom display name') }}">
                            </div>
                            <x-form.error name="display_name" />
                        </div>

                        <!-- Public Toggle -->
                        <div class="space-y-2">
                            <x-form.label class="font-main">
                                {{ __('Visibility') }}
                            </x-form.label>
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
                        <x-button variant="neutral" size="lg" class="focus:outline-none focus:ring-2 focus:ring-neutral-500 focus:ring-offset-2" type="button" wire:click="cancel">
                            {{ __('Cancel') }}
                        </x-button>
                        <x-button variant="secondary" size="lg" class="shadow-sm focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2" type="submit">
                            <x-icon name="check" class="w-4 h-4 mr-2" />
                            {{ $editingId ? __('Update Link') : __('Add Link') }}
                        </x-button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Social Links List -->
        @if (empty($social_links))
            <div class="text-center py-12">
                <div class="w-16 h-16 mx-auto bg-neutral-100 rounded-full flex items-center justify-center mb-4">
                    <x-icon name="link" class="w-8 h-8 text-neutral-400" />
                </div>
                <h3 class="text-lg font-semibold text-neutral-900 font-main mb-2">{{ __('No social links yet') }}</h3>
                <p class="text-neutral-600 font-main mb-6">
                    {{ __('Connect your social media profiles and professional links to showcase your online presence.') }}
                </p>
                @if (!$showAddForm)
                    <x-button variant="secondary" size="lg" class="shadow-sm focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2" wire:click="showAdd">
                        <x-icon name="plus" class="w-4 h-4 mr-2" />
                        {{ __('Add Your First Link') }}
                    </x-button>
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
                                        <x-icon name="arrow-top-right-on-square" class="w-3 h-3 inline ml-1" />
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
                                        <x-icon name="chevron-down" class="w-4 h-4" />
                                    </button>
                                @endif

                                <!-- Edit Button -->
                                <button wire:click="edit({{ $link['id'] }})"
                                    class="p-2 text-secondary hover:text-secondary/80 hover:bg-secondary/10 rounded-lg transition-all duration-200"
                                    title="{{ __('Edit') }}">
                                    <x-icon name="pencil-square" class="w-4 h-4" />
                                </button>

                                <!-- Delete Button -->
                                <button wire:click="delete({{ $link['id'] }})"
                                    wire:confirm="{{ __('Are you sure you want to delete this social link?') }}"
                                    class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-all duration-200"
                                    title="{{ __('Delete') }}">
                                    <x-icon name="trash" class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <x-success-toast event="social-links-updated" :message="__('Social links updated successfully!')"
        variant="floating" :timeout="4000" />
</section>
