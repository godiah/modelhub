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

<div>
    <x-panel :title="__('Social accounts')" :description="__('Links to your professional profiles and portfolio.')">
        @if (!$showAddForm)
            <x-slot:actions>
                <x-btn variant="secondary" type="button" wire:click="showAdd" wire:target="showAdd">
                    <x-icon name="plus" class="h-4 w-4" />
                    {{ __('Add link') }}
                </x-btn>
            </x-slot:actions>
        @endif

        <!-- Add / edit -->
        @if ($showAddForm)
            <form wire:submit="save" class="mb-5 space-y-4 rounded-xl border border-neutral-200 bg-neutral-50/60 p-5">
                <p class="text-sm font-semibold text-neutral-900">
                    {{ $editingId ? __('Edit link') : __('Add a link') }}</p>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field id="social_network_id" name="social_network_id" type="select" :label="__('Platform')"
                        wire:model.live="social_network_id" icon="link">
                        <option value="0">{{ __('Select platform') }}</option>
                        @foreach ($this->socialNetworks as $network)
                            <option value="{{ $network->id }}">{{ $network->name }}</option>
                        @endforeach
                    </x-field>

                    <x-field id="username" name="username" :label="__('Username (optional)')" wire:model.live="username"
                        icon="user" placeholder="{{ __('your_username') }}"
                        :hint="__('We build the URL for known platforms.')" />
                </div>

                <x-field id="url" name="url" type="url" :label="__('URL')" wire:model="url" icon="link"
                    placeholder="https://example.com/profile" required />

                <div class="grid items-end gap-4 sm:grid-cols-2">
                    <x-field id="display_name" name="display_name" :label="__('Display name (optional)')"
                        wire:model="display_name" icon="tag" placeholder="{{ __('Custom display name') }}" />

                    <label for="is_public" class="flex cursor-pointer items-center gap-3 pb-2.5">
                        <input type="checkbox" wire:model="is_public" id="is_public" class="peer sr-only">
                        <span
                            class="relative h-6 w-11 shrink-0 rounded-full bg-neutral-300 transition-colors after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:bg-teal-600 peer-checked:after:translate-x-5 peer-focus-visible:ring-4 peer-focus-visible:ring-secondary/25"></span>
                        <span class="text-sm text-neutral-700">{{ __('Show on my profile') }}</span>
                    </label>
                </div>

                <div class="flex flex-wrap justify-end gap-3 pt-1">
                    <x-btn variant="secondary" type="button" wire:click="cancel">{{ __('Cancel') }}</x-btn>
                    <x-btn wire:target="save">{{ $editingId ? __('Update link') : __('Add link') }}</x-btn>
                </div>
            </form>
        @endif

        <!-- List -->
        @if (empty($social_links))
            @if (!$showAddForm)
                <div class="flex flex-col items-center rounded-xl border border-dashed border-neutral-300 px-6 py-10 text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 text-neutral-500">
                        <x-icon name="link" class="h-6 w-6" />
                    </span>
                    <p class="mt-4 text-sm font-semibold text-neutral-900">{{ __('No links yet') }}</p>
                    <p class="mt-1 max-w-xs text-sm text-tertiary">
                        {{ __('Add your LinkedIn, portfolio or social profiles so clients can find your work.') }}</p>
                    <x-btn class="mt-4" type="button" wire:click="showAdd" wire:target="showAdd">
                        <x-icon name="plus" class="h-4 w-4" />
                        {{ __('Add your first link') }}
                    </x-btn>
                </div>
            @endif
        @else
            <ul class="space-y-2.5">
                @foreach ($social_links as $index => $link)
                    @php($color = preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $link['social_network']['color']) ? $link['social_network']['color'] : '#6B7280')
                    <li x-data="{ confirming: false }"
                        class="group flex flex-wrap items-center gap-3 rounded-xl border border-neutral-200 bg-white p-3 transition-colors duration-150 hover:border-neutral-300 sm:flex-nowrap">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-neutral-50">
                            <i class="{{ $link['social_network']['icon'] }} text-xl" style="color: {{ $color }}" aria-hidden="true"></i>
                        </span>

                        <div class="min-w-[9rem] flex-1">
                            <div class="flex items-center gap-2">
                                <p class="truncate text-sm font-semibold text-neutral-900">{{ $link['social_network']['name'] }}</p>
                                <x-badge :tone="$link['is_public'] ? 'green' : 'neutral'" class="shrink-0 px-2 py-0.5 text-[11px] font-medium">
                                    {{ $link['is_public'] ? __('Public') : __('Private') }}
                                </x-badge>
                            </div>
                            <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer"
                                class="block truncate text-sm text-tertiary transition-colors hover:text-teal-700">
                                {{ $link['display_name'] ?: ($link['username'] ?: $link['url']) }}
                            </a>
                        </div>

                        <!-- Actions (always visible on touch, revealed on hover/focus on desktop) -->
                        <div x-show="!confirming"
                            class="ml-auto flex shrink-0 items-center gap-0.5 lg:opacity-0 lg:transition-opacity lg:duration-150 lg:group-focus-within:opacity-100 lg:group-hover:opacity-100">
                            @if ($index > 0)
                                <button type="button" wire:click="moveUp({{ $link['id'] }})" title="{{ __('Move up') }}"
                                    aria-label="{{ __('Move up') }}"
                                    class="rounded-lg p-2 text-neutral-400 transition-colors hover:bg-neutral-100 hover:text-neutral-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
                                    <x-icon name="chevron-down" class="h-4 w-4 rotate-180" />
                                </button>
                            @endif
                            @if ($index < count($social_links) - 1)
                                <button type="button" wire:click="moveDown({{ $link['id'] }})" title="{{ __('Move down') }}"
                                    aria-label="{{ __('Move down') }}"
                                    class="rounded-lg p-2 text-neutral-400 transition-colors hover:bg-neutral-100 hover:text-neutral-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
                                    <x-icon name="chevron-down" class="h-4 w-4" />
                                </button>
                            @endif
                            <button type="button" wire:click="edit({{ $link['id'] }})" title="{{ __('Edit') }}"
                                aria-label="{{ __('Edit') }}"
                                class="rounded-lg p-2 text-neutral-400 transition-colors hover:bg-neutral-100 hover:text-teal-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
                                <x-icon name="pencil-square" class="h-4 w-4" />
                            </button>
                            <button type="button" @click="confirming = true" title="{{ __('Delete') }}"
                                aria-label="{{ __('Delete') }}"
                                class="rounded-lg p-2 text-neutral-400 transition-colors hover:bg-red-50 hover:text-red-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-300">
                                <x-icon name="trash" class="h-4 w-4" />
                            </button>
                        </div>

                        <!-- Inline delete confirmation -->
                        <div x-show="confirming" x-cloak class="ml-auto flex shrink-0 items-center gap-2 text-sm">
                            <span class="hidden text-tertiary sm:inline">{{ __('Delete this link?') }}</span>
                            <button type="button" wire:click="delete({{ $link['id'] }})"
                                class="rounded-lg bg-red-600 px-3 py-1.5 font-semibold text-white transition-colors hover:bg-red-700">{{ __('Delete') }}</button>
                            <button type="button" @click="confirming = false"
                                class="rounded-lg border border-neutral-300 px-3 py-1.5 font-semibold text-neutral-700 transition-colors hover:bg-neutral-50">{{ __('Keep') }}</button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-panel>

    <x-success-toast event="social-links-updated" :message="__('Social links updated successfully!')" variant="floating"
        :timeout="4000" />
</div>
