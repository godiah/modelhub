<?php

use App\Models\Skill;
use App\Models\Software;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public $avatar;
    public string $professional_info = '';
    public string $location = '';
    public string $telephone_number = '';
    public array $selected_skills = [];
    public array $selected_software = [];

    public $avatar_preview;
    public $current_avatar;

    /**
     * Mount the component with existing profile data.
     */
    public function mount(): void
    {
        $profile = Auth::user()->profile;

        if ($profile) {
            $this->professional_info = $profile->professional_info ?? '';
            $this->location = $profile->location ?? '';
            $this->telephone_number = $profile->telephone_number ?? '';
            $this->current_avatar = $profile->avatar;
        }

        // Load user's existing skills and software
        $this->selected_skills = Auth::user()->skills()->pluck('skills.id')->toArray();
        $this->selected_software = Auth::user()->software()->pluck('software.id')->toArray();
    }

    /**
     * Get all active skills for the select dropdown.
     */
    public function getSkillsProperty()
    {
        return Skill::active()->orderBy('name')->get();
    }

    /**
     * Get all active software for the select dropdown.
     */
    public function getSoftwareProperty()
    {
        return Software::active()->orderBy('name')->get();
    }

    /**
     * Handle avatar upload and preview.
     */
    public function updatedAvatar()
    {
        $this->validate([
            'avatar' => 'image|max:1024',
        ]);

        $this->avatar_preview = $this->avatar->temporaryUrl();
    }

    /**
     * Update the user profile.
     */
    public function updateProfile(): void
    {
        $validated = $this->validate([
            'avatar' => 'nullable|image|max:1024',
            'professional_info' => 'nullable|string|max:1000',
            'location' => 'nullable|string|max:255',
            'telephone_number' => 'nullable|string|max:20',
            'selected_skills' => 'array',
            'selected_skills.*' => 'exists:skills,id',
            'selected_software' => 'array',
            'selected_software.*' => 'exists:software,id',
        ]);

        $user = Auth::user();
        $profile = $user->getOrCreateProfile();

        // Handle avatar upload
        $avatarPath = $profile->avatar;
        if ($this->avatar) {
            // Delete old avatar if exists
            if ($avatarPath && Storage::disk('public')->exists($avatarPath)) {
                Storage::disk('public')->delete($avatarPath);
            }

            $avatarPath = $this->avatar->store('avatars', 'public');
        }

        // Update profile
        $profile->update([
            'avatar' => $avatarPath,
            'professional_info' => $validated['professional_info'],
            'location' => $validated['location'],
            'telephone_number' => $validated['telephone_number'],
        ]);

        // Sync skills and software
        $user->skills()->sync($validated['selected_skills']);
        $user->software()->sync($validated['selected_software']);

        // Reset avatar upload
        $this->reset('avatar', 'avatar_preview');
        $this->current_avatar = $avatarPath;

        $this->dispatch('profile-details-updated');
    }

    /**
     * Remove current avatar.
     */
    public function removeAvatar(): void
    {
        $profile = Auth::user()->profile;

        if ($profile && $profile->avatar) {
            if (Storage::disk('public')->exists($profile->avatar)) {
                Storage::disk('public')->delete($profile->avatar);
            }

            $profile->update(['avatar' => null]);
            $this->current_avatar = null;
        }
    }
}; ?>

<section class="bg-white rounded-xl shadow-sm border border-neutral-200 overflow-hidden">
    <x-section-header :title="__('Bio Information')"
        :subtitle="__('Update your bio information, skills, and preferred tools')">
        <x-slot:icon>
            <x-icon name="user" class="w-5 h-5 text-white" />
        </x-slot:icon>
    </x-section-header>

    <!-- Form Section -->
    <div class="p-6">
        <form wire:submit="updateProfile" class="space-y-8">
            <!-- Avatar Section -->
            <div class="space-y-4">
                <x-form.label class="font-main">
                    {{ __('Profile Avatar') }}
                </x-form.label>

                <div class="flex items-start space-x-6">
                    <!-- Current Avatar Display -->
                    <div class="flex-shrink-0">
                        @if ($avatar_preview)
                            <div class="relative">
                                <img src="{{ $avatar_preview }}" alt="Avatar preview"
                                    class="w-24 h-24 rounded-full object-cover border-2 border-secondary shadow-lg">
                                <div
                                    class="absolute -top-2 -right-2 w-6 h-6 bg-secondary rounded-full flex items-center justify-center">
                                    <x-icon name="check" class="w-3 h-3 text-white" />
                                </div>
                            </div>
                        @elseif($current_avatar)
                            <img src="{{ asset('storage/' . $current_avatar) }}" alt="Current avatar"
                                class="w-24 h-24 rounded-full object-cover border-2 border-neutral-200 shadow-lg">
                        @else
                            @php
                                $user = Auth::user();
                            @endphp
                            <div
                                class="w-24 h-24 rounded-full bg-primary/10 border-2 border-primary/20 flex items-center justify-center shadow-lg">
                                <span class="text-2xl font-semibold text-primary font-main">
                                    {{ $user->getInitials() }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <!-- Avatar Upload Controls -->
                    <div class="flex-1 space-y-4">
                        <div class="relative">
                            <input type="file" wire:model="avatar" accept="image/*" id="avatar-upload"
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                            <div
                                class="border-2 border-dashed border-neutral-300 rounded-lg p-6 text-center hover:border-secondary transition-colors duration-200">
                                <x-icon name="cloud-arrow-up" class="mx-auto h-8 w-8 text-neutral-400 mb-2" />
                                <p class="text-sm text-neutral-600 font-main">
                                    <span class="font-medium text-secondary">{{ __('Click to upload') }}</span>
                                    {{ __(' or drag and drop') }}
                                </p>
                                <p class="text-xs text-neutral-500 font-main mt-1">
                                    {{ __('PNG, JPG, JPEG up to 1MB (100x100px recommended)') }}
                                </p>
                            </div>
                        </div>

                        @if ($current_avatar)
                            <button type="button" wire:click="removeAvatar"
                                class="inline-flex items-center text-sm text-red-600 hover:text-red-800 font-main transition-colors duration-200">
                                <x-icon name="trash" class="w-4 h-4 mr-1" />
                                {{ __('Remove current avatar') }}
                            </button>
                        @endif
                    </div>
                </div>

                <x-form.error name="avatar" />
            </div>

            <!-- Professional Info -->
            <div class="space-y-2" x-data="{
                charCount: 0,
                maxChars: 1000,
                init() {
                    this.charCount = $wire.professional_info ? $wire.professional_info.length : 0;
                    this.$watch('$wire.professional_info', value => {
                        this.charCount = value ? value.length : 0;
            
                        // Optional: Truncate if exceeds max length
                        if (this.charCount > this.maxChars) {
                            $wire.set('professional_info', value.substring(0, this.maxChars));
                        }
                    });
                }
            }">
                <x-form.label class="font-main" for="professional_info">
                    {{ __('Professional Information') }}
                </x-form.label>
                <div class="relative">
                    <textarea wire:model.debounce.300ms="professional_info" id="professional_info" rows="5" maxlength="1000"
                        class="text-sm text-justify block w-full px-4 py-3 border border-neutral-300 rounded-lg shadow-sm placeholder-neutral-400 focus:outline-none focus:ring-1 focus:ring-secondary focus:border-secondary transition-colors duration-200 font-main resize-none"
                        placeholder="{{ __('Tell us about your professional background, experience, and expertise...') }}"></textarea>

                    <!-- Character Counter -->
                    <div class="absolute bottom-3 right-3 flex items-center space-x-1 font-main">
                        <span class="text-xs font-medium transition-colors duration-200"
                            :class="{
                                'text-neutral-400': charCount < maxChars * 0.8,
                                'text-accent': charCount >= maxChars * 0.8 && charCount < maxChars,
                                'text-red-500': charCount >= maxChars
                            }">
                            <span x-text="charCount"></span>
                        </span>
                        <span class="text-xs text-neutral-400 font-main">/</span>
                        <span class="text-xs text-neutral-400 font-main" x-text="maxChars"></span>

                        <!-- Visual indicator icon -->
                        <template x-if="charCount >= maxChars * 0.8">
                            <svg x-show="charCount >= maxChars" class="w-4 h-4 text-red-500" fill="currentColor"
                                viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            <svg x-show="charCount >= maxChars * 0.8 && charCount < maxChars"
                                class="w-4 h-4 text-accent" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        </template>
                    </div>

                    <!-- Progress Bar -->
                    <div class="h-1 w-full bg-neutral-100 rounded-full mt-1 overflow-hidden">
                        <div class="h-full transition-all duration-300 ease-out rounded-full"
                            :class="{
                                'bg-secondary': charCount < maxChars * 0.8,
                                'bg-accent': charCount >= maxChars * 0.8 && charCount < maxChars,
                                'bg-red-500': charCount >= maxChars
                            }"
                            :style="`width: ${Math.min(charCount / maxChars * 100, 100)}%`"></div>
                    </div>
                </div>

                <!-- Character Limit Helper Text -->
                <p class="text-xs transition-colors duration-200 font-main"
                    :class="{
                        'text-neutral-500': charCount < maxChars * 0.8,
                        'text-accent': charCount >= maxChars * 0.8 && charCount < maxChars,
                        'text-red-500': charCount >= maxChars
                    }">
                    <span x-show="charCount < maxChars * 0.8">
                        {{ __('Describe your professional background, experience, and expertise.') }}
                    </span>
                    <span x-show="charCount >= maxChars * 0.8 && charCount < maxChars">
                        {{ __('You\'re approaching the character limit.') }}
                    </span>
                    <span x-show="charCount >= maxChars">
                        {{ __('You\'ve reached the maximum character limit.') }}
                    </span>
                </p>

                <x-form.error name="professional_info" />
            </div>

            <!-- Skills Section -->
            <div class="space-y-2">
                <x-form.label class="font-main">
                    {{ __('Area of Expertise (Skills)') }}
                </x-form.label>
                <x-form.error name="selected_skills" class="mb-2" />

                <div class="relative" x-data="multiSelect('skills', @js($this->skills), @js($selected_skills ?? []))">
                    <div
                        class="border border-neutral-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-secondary focus-within:border-secondary transition-all duration-200">
                        <!-- Search Input -->
                        <div class="p-3 bg-white relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <x-icon name="magnifying-glass" class="h-5 w-5 ml-2 text-neutral-400" />
                            </div>
                            <input type="text" x-model="searchTerm" @focus="showDropdown = true"
                                @click.away="showDropdown = false" placeholder="{{ __('Search for skills...') }}"
                                class="w-full pl-10 px-4 py-3 border-0 rounded-lg text-sm focus:ring-0 focus:outline-none font-main placeholder-neutral-400" />
                        </div>

                        <!-- Selected Options -->
                        <div x-show="selectedItems.length > 0"
                            class="px-3 py-2 flex flex-wrap gap-2 bg-neutral-50 border-t border-neutral-200">
                            <template x-for="item in selectedItems" :key="item.id">
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-secondary/20 text-secondary font-main">
                                    <span x-text="item.name"></span>
                                    <button type="button" @click="removeItem(item)"
                                        class="ml-2 text-secondary/70 hover:text-secondary">
                                        <x-icon name="x-mark-solid" class="w-3 h-3" />
                                    </button>
                                </span>
                            </template>
                        </div>

                        <!-- Dropdown Options -->
                        <div x-show="showDropdown && filteredItems.length > 0"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="max-h-60 overflow-y-auto border-t border-neutral-200">
                            <div class="p-2">
                                <template x-for="item in filteredItems" :key="item.id">
                                    <button type="button" @click="toggleItem(item)"
                                        class="w-full text-left px-3 py-2 text-sm rounded-lg hover:bg-secondary/10 transition-colors duration-150 font-main"
                                        :class="isSelected(item) ? 'bg-secondary/20 text-secondary font-medium' :
                                            'text-neutral-700'">
                                        <div class="flex items-center justify-between">
                                            <span x-text="item.name"></span>
                                            <x-icon name="check-solid" x-show="isSelected(item)" class="w-4 h-4 text-secondary" />
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden select for form submission -->
                    <select wire:model="selected_skills" multiple class="hidden">
                        <template x-for="item in selectedItems" :key="item.id">
                            <option :value="item.id" selected x-text="item.name"></option>
                        </template>
                    </select>
                </div>
                <p class="text-xs text-neutral-500 font-main">
                    {{ __('Search and select multiple skills that match your expertise') }}</p>
            </div>

            <!-- Software Section -->
            <div class="space-y-2">
                <x-form.label class="font-main">
                    {{ __('Preferred Tools (Software)') }}
                </x-form.label>
                <x-form.error name="selected_software" class="mb-2" />

                <div class="relative" x-data="multiSelect('software', @js($this->software), @js($selected_software ?? []))">
                    <div
                        class="border border-neutral-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-secondary focus-within:border-secondary transition-all duration-200">
                        <!-- Search Input -->
                        <div class="p-3 bg-white relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <x-icon name="magnifying-glass" class="h-5 w-5 ml-2 text-neutral-400" />
                            </div>
                            <input type="text" x-model="searchTerm" @focus="showDropdown = true"
                                @click.away="showDropdown = false"
                                placeholder="{{ __('Search for software tools...') }}"
                                class="w-full pl-10 px-4 py-3 border-0 rounded-lg text-sm focus:ring-0 focus:outline-none font-main placeholder-neutral-400" />
                        </div>

                        <!-- Selected Options -->
                        <div x-show="selectedItems.length > 0"
                            class="px-3 py-2 flex flex-wrap gap-2 bg-neutral-50 border-t border-neutral-200">
                            <template x-for="item in selectedItems" :key="item.id">
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-accent/20 text-accent font-main">
                                    <span x-text="item.name"></span>
                                    <button type="button" @click="removeItem(item)"
                                        class="ml-2 text-accent/70 hover:text-accent">
                                        <x-icon name="x-mark-solid" class="w-3 h-3" />
                                    </button>
                                </span>
                            </template>
                        </div>

                        <!-- Dropdown Options -->
                        <div x-show="showDropdown && filteredItems.length > 0"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="max-h-60 overflow-y-auto border-t border-neutral-200">
                            <div class="p-2">
                                <template x-for="item in filteredItems" :key="item.id">
                                    <button type="button" @click="toggleItem(item)"
                                        class="w-full text-left px-3 py-2 text-sm rounded-lg hover:bg-accent/10 transition-colors duration-150 font-main"
                                        :class="isSelected(item) ? 'bg-accent/20 text-accent font-medium' : 'text-neutral-700'">
                                        <div class="flex items-center justify-between">
                                            <span x-text="item.name"></span>
                                            <x-icon name="check-solid" x-show="isSelected(item)" class="w-4 h-4 text-accent" />
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden select for form submission -->
                    <select wire:model="selected_software" multiple class="hidden">
                        <template x-for="item in selectedItems" :key="item.id">
                            <option :value="item.id" selected x-text="item.name"></option>
                        </template>
                    </select>
                </div>
                <p class="text-xs text-neutral-500 font-main">
                    {{ __('Search and select multiple software tools you prefer to work with') }}</p>
            </div>

            <!-- Location -->
            <div class="space-y-2">
                <x-form.label class="font-main" for="location">
                    {{ __('Location') }}
                </x-form.label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-neutral-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                            </path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <input type="text" wire:model="location" id="location"
                        class="text-sm block w-full pl-10 pr-3 py-3 border border-neutral-300 rounded-lg shadow-sm placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-colors duration-200 font-main"
                        placeholder="{{ __('City, Country') }}">
                </div>
                <x-form.error name="location" />
            </div>

            <!-- Telephone -->
            <div class="space-y-2">
                <x-form.label class="font-main" for="telephone_number">
                    {{ __('Telephone Number') }}
                </x-form.label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <x-icon name="phone" class="h-5 w-5 text-neutral-400" />
                    </div>
                    <input type="tel" wire:model="telephone_number" id="telephone_number"
                        class="text-sm block w-full pl-10 pr-3 py-3 border border-neutral-300 rounded-lg shadow-sm placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary transition-colors duration-200 font-main"
                        placeholder="{{ __('+254 71234 6789') }}">
                </div>
                <x-form.error name="telephone_number" />
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-between pt-6 border-t border-neutral-200">
                <div class="text-xs text-neutral-500 font-main">
                    {{ __('All fields are optional but help create a complete profile') }}
                </div>

                <x-button size="lg" class="text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2" type="submit" wire:loading.attr="disabled">

                    <span wire:loading.remove wire:target="updateProfile" class="flex items-center">
                        <x-icon name="check" class="w-4 h-4 mr-2" />
                        {{ __('Update Bio Details') }}
                    </span>

                    <span wire:loading wire:target="updateProfile"
                        class="inline-flex items-center space-x-2 text-white">
                        <x-spinner class="h-4 w-4" />
                        <span>{{ __('Updating...') }}</span>
                    </span>

                </x-button>
            </div>
        </form>
    </div>

    <x-success-toast event="profile-details-updated" :message="__('Profile updated successfully!')"
        variant="floating" :timeout="4000" />
</section>

<!-- Alpine.js Multi-Select Component -->
<script>
    function multiSelect(fieldName, allItems, selectedIds = []) {
        return {
            searchTerm: '',
            showDropdown: false,
            allItems: allItems,
            selectedItems: [],

            init() {
                // Initialize selected items based on selectedIds
                this.selectedItems = this.allItems.filter(item =>
                    selectedIds.includes(item.id)
                );

                // Update Livewire model
                this.updateLivewireModel();
            },

            get filteredItems() {
                return this.allItems.filter(item => {
                    const matchesSearch = item.name.toLowerCase().includes(this.searchTerm.toLowerCase());
                    const notSelected = !this.isSelected(item);
                    return matchesSearch && notSelected;
                });
            },

            isSelected(item) {
                return this.selectedItems.some(selected => selected.id === item.id);
            },

            toggleItem(item) {
                if (this.isSelected(item)) {
                    this.removeItem(item);
                } else {
                    this.addItem(item);
                }
            },

            addItem(item) {
                this.selectedItems.push(item);
                this.updateLivewireModel();
                this.searchTerm = '';
            },

            removeItem(item) {
                this.selectedItems = this.selectedItems.filter(selected => selected.id !== item.id);
                this.updateLivewireModel();
            },

            updateLivewireModel() {
                const selectedIds = this.selectedItems.map(item => item.id);
                if (fieldName === 'skills') {
                    this.$wire.set('selected_skills', selectedIds);
                } else if (fieldName === 'software') {
                    this.$wire.set('selected_software', selectedIds);
                }
            }
        }
    }
</script>
