<?php

use App\Models\Skill;
use App\Models\Software;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $professional_info = '';
    public string $location = '';
    public string $telephone_number = '';
    public array $selected_skills = [];
    public array $selected_software = [];

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
     * Update the user profile.
     */
    public function updateProfile(): void
    {
        $validated = $this->validate([
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

        // Update profile
        $profile->update([
            'professional_info' => $validated['professional_info'],
            'location' => $validated['location'],
            'telephone_number' => $validated['telephone_number'],
        ]);

        // Sync skills and software
        $user->skills()->sync($validated['selected_skills']);
        $user->software()->sync($validated['selected_software']);

        $this->dispatch('profile-details-updated');
    }

}; ?>

<div class="space-y-6">
    <!-- Avatar: its own form, saved at once, so it sits outside the profile form below -->
    <x-panel :title="__('Avatar')" :description="__('This is how you appear across ModelHub. Everyone starts with a random one, so change it to whichever you like.')">
        <x-avatar-picker kind="people" :current="auth()->user()->avatar" :fallback="auth()->id()" :action="route('profile.avatar.update')" />
    </x-panel>

    <form wire:submit="updateProfile" class="space-y-6">
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
