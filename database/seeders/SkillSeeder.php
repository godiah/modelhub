<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{

    public function run(): void
    {
        $skills = [
            'Polygonal',
            'Subdivision Modelling',
            'Hard-Surface Modelling',
            'Organic Modelling',
            'Sculpting',
            'UV Mapping',
            'Unwrapping',
            'Texturing (PBR, procedural, hand-painted)',
            'Detailing & Surface Finish',
            'Optimization & Level of Detail (LOD)',
            'Retopology',
            'Rigging',
            'Skinning',
            'Animation Basics',
            'Rendering & Lighting',
            'File Format Management & Export',
            'Reference Research & Accuracy'
        ];

        foreach ($skills as $skillName) {
            Skill::updateOrCreate(
                ['name' => $skillName],
                ['is_active' => true],
                ['created_at' => now()],
                ['updates_at' => now()]
            );
        }
    }
}
