<?php

namespace App\Support\Profile;

use App\Models\User;

/**
 * What "a complete profile" means, shared by the dashboard card and the profile page checklist.
 */
final class ProfileCompleteness
{
    /**
     * @return array{percent: int, items: array<string, bool>, next_step: string|null}
     */
    public static function for(User $user): array
    {
        $user->loadMissing(['profile', 'skills', 'software']);
        $profile = $user->profile;

        $items = [
            __('Add a profile photo') => (bool) $profile?->avatar,
            __('Describe your professional background') => (bool) $profile?->professional_info,
            __('Add your location') => (bool) $profile?->location,
            __('Add a phone number') => (bool) $profile?->telephone_number,
            __('Add your skills') => $user->skills->isNotEmpty(),
            __('Add the software you use') => $user->software->isNotEmpty(),
        ];

        return [
            'percent' => (int) round(count(array_filter($items)) / count($items) * 100),
            'items' => $items,
            'next_step' => array_search(false, $items, true) ?: null,
        ];
    }
}
