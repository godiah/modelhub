<?php

namespace Database\Seeders;

use App\Models\SocialNetwork;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SocialNetworksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $socialNetworks = [
            [
                'name' => 'Facebook',
                'slug' => 'facebook',
                'icon' => 'fab fa-facebook-f',
                'color' => '#1877F2',
                'base_url' => 'https://facebook.com/',
                'sort_order' => 1,
            ],
            [
                'name' => 'Twitter/X',
                'slug' => 'twitter',
                'icon' => 'fab fa-x-twitter',
                'color' => '#000000',
                'base_url' => 'https://x.com/',
                'sort_order' => 2,
            ],
            [
                'name' => 'Instagram',
                'slug' => 'instagram',
                'icon' => 'fab fa-instagram',
                'color' => '#E4405F',
                'base_url' => 'https://instagram.com/',
                'sort_order' => 3,
            ],
            [
                'name' => 'LinkedIn',
                'slug' => 'linkedin',
                'icon' => 'fab fa-linkedin-in',
                'color' => '#0A66C2',
                'base_url' => 'https://linkedin.com/in/',
                'sort_order' => 4,
            ],
            [
                'name' => 'YouTube',
                'slug' => 'youtube',
                'icon' => 'fab fa-youtube',
                'color' => '#FF0000',
                'base_url' => 'https://youtube.com/',
                'sort_order' => 5,
            ],
            [
                'name' => 'TikTok',
                'slug' => 'tiktok',
                'icon' => 'fab fa-tiktok',
                'color' => '#000000',
                'base_url' => 'https://tiktok.com/@',
                'sort_order' => 6,
            ],
            [
                'name' => 'GitHub',
                'slug' => 'github',
                'icon' => 'fab fa-github',
                'color' => '#181717',
                'base_url' => 'https://github.com/',
                'sort_order' => 7,
            ],
            [
                'name' => 'Dribbble',
                'slug' => 'dribbble',
                'icon' => 'fab fa-dribbble',
                'color' => '#EA4C89',
                'base_url' => 'https://dribbble.com/',
                'sort_order' => 8,
            ],
            [
                'name' => 'Behance',
                'slug' => 'behance',
                'icon' => 'fab fa-behance',
                'color' => '#1769FF',
                'base_url' => 'https://behance.net/',
                'sort_order' => 9,
            ],
            [
                'name' => 'Medium',
                'slug' => 'medium',
                'icon' => 'fab fa-medium',
                'color' => '#000000',
                'base_url' => 'https://medium.com/@',
                'sort_order' => 10,
            ],
            [
                'name' => 'Website',
                'slug' => 'website',
                'icon' => 'fas fa-globe',
                'color' => '#6B7280',
                'base_url' => '',
                'sort_order' => 11,
            ],
        ];

        foreach ($socialNetworks as $network) {
            SocialNetwork::create($network);
        }
    }
}
