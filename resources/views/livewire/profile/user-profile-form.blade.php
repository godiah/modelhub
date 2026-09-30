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

<div>
    <form wire:submit="updateProfile" class="space-y-6">
        <!-- Photo -->
        <x-panel :title="__('Profile photo')" :description="__('This is how you appear across ModelHub.')">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                <div class="relative shrink-0">
                    @if ($avatar_preview)
                        <img src="{{ $avatar_preview }}" alt="{{ __('Avatar preview') }}"
                            class="h-24 w-24 rounded-full border-2 border-secondary object-cover">
                        <span
                            class="absolute -right-1 -top-1 flex h-6 w-6 items-center justify-center rounded-full bg-secondary text-white ring-2 ring-white">
                            <x-icon name="check" class="h-3.5 w-3.5" />
                        </span>
                    @elseif ($current_avatar)
                        <img src="{{ asset('storage/' . $current_avatar) }}" alt="{{ __('Current avatar') }}"
                            class="h-24 w-24 rounded-full border border-neutral-200 object-cover">
                    @else
                        <x-user-avatar :user="auth()->user()" size="h-24 w-24" class="!text-2xl" />
                    @endif
                </div>

                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-3">
                        <input type="file" wire:model="avatar" accept="image/*" id="avatar-upload" class="peer sr-only">
                        <label for="avatar-upload"
                            class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-neutral-300 bg-white px-4 py-2 font-secondary text-sm font-semibold text-neutral-700 transition-colors duration-200 hover:bg-neutral-50 peer-focus-visible:ring-4 peer-focus-visible:ring-neutral-200">
                            <x-icon name="cloud-arrow-up" class="h-4 w-4" />
                            {{ __('Upload new') }}
                        </label>

                        @if ($current_avatar && !$avatar_preview)
                            <x-btn variant="ghost" type="button" wire:click="removeAvatar" wire:target="removeAvatar"
                                class="!text-red-600 hover:!bg-red-50">
                                <x-icon name="trash" class="h-4 w-4" />
                                {{ __('Remove') }}
                            </x-btn>
                        @endif
                    </div>
                    <p class="mt-2 text-xs text-tertiary">
                        <span wire:loading wire:target="avatar">{{ __('Uploading…') }}</span>
                        <span wire:loading.remove wire:target="avatar">
                            @if ($avatar_preview)
                                {{ __('Preview only. Save changes to apply this photo.') }}
                            @else
                                {{ __('PNG or JPG, up to 1 MB. Square images look best.') }}
                            @endif
                        </span>
                    </p>
                    <x-input-error :messages="$errors->get('avatar')" class="mt-1.5" />
                </div>
            </div>
        </x-panel>

        <!-- About -->
        <x-panel :title="__('About you')" :description="__('A short summary of your background and what you do best.')">
            <div x-data="{
                count: 0,
                max: 1000,
                init() {
                    this.count = ($wire.professional_info || '').length;
                    this.$watch('$wire.professional_info', value => this.count = (value || '').length);
                },
            }">
                <x-field id="professional_info" name="professional_info" type="textarea" wire:model="professional_info"
                    rows="6" maxlength="1000"
                    placeholder="{{ __('Tell clients about your experience, the kind of work you take on and how you like to collaborate…') }}" />
                <p class="mt-1.5 text-right text-xs tabular-nums"
                    :class="count >= max ? 'text-red-600' : (count >= max * 0.8 ? 'text-amber-600' : 'text-tertiary')">
                    <span x-text="count"></span> / <span x-text="max"></span>
                </p>
            </div>
        </x-panel>

        <!-- Skills and software -->
        <x-panel :title="__('Skills and tools')" :description="__('Help clients find you by what you know and the software you use.')">
            <div class="space-y-5">
                <x-tag-picker field="selected_skills" :label="__('Area of expertise')"
                    :items="$this->skills->map(fn($skill) => ['id' => $skill->id, 'name' => $skill->name])->all()"
                    :selected="$selected_skills" :placeholder="__('Search skills…')" tone="teal" />

                <x-tag-picker field="selected_software" :label="__('Preferred software')"
                    :items="$this->software->map(fn($tool) => ['id' => $tool->id, 'name' => $tool->name])->all()"
                    :selected="$selected_software" :placeholder="__('Search software…')" tone="neutral" />
            </div>
        </x-panel>

        <!-- Contact -->
        <x-panel :title="__('Location and contact')" :description="__('Where you work from and how to reach you.')">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field id="location" name="location" :label="__('Location')" wire:model="location" icon="map-pin"
                    placeholder="{{ __('City, Country') }}" />
                <x-field id="telephone_number" name="telephone_number" type="tel" :label="__('Phone number')"
                    wire:model="telephone_number" icon="phone" placeholder="+254 712 345 678" />
            </div>
        </x-panel>

        <!-- Save bar -->
        <div
            class="sticky bottom-4 z-10 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-neutral-200 bg-white/90 px-5 py-3 shadow-lg backdrop-blur">
            <p class="text-xs text-tertiary">{{ __('All fields are optional, but a complete profile wins more work.') }}</p>
            <x-btn wire:target="updateProfile">{{ __('Save changes') }}</x-btn>
        </div>
    </form>

    <x-success-toast event="profile-details-updated" :message="__('Profile updated successfully!')" variant="floating"
        :timeout="4000" />
</div>
