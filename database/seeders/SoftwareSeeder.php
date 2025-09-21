<?php

namespace Database\Seeders;

use App\Models\Software;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SoftwareSeeder extends Seeder
{
    public function run(): void
    {
        $software = [
            'Blender',
            'Autodesk Maya',
            'Autodesk 3ds Max',
            'ZBrush',
            'Substance Painter',
            'Substance Designer',
            'Fusion 360',
            'SolidWorks',
            'FreeCAD',
            'Houdini',
            'SketchUp',
            'Rhino 3D',
            'Marvelous Designer',
            'Cinema 4D',
            'Unity',
            'Unreal Engine',
            'V-Ray',
            'Arnold',
            'Octane Render'
        ];

        foreach ($software as $softwareName) {
            Software::updateOrCreate(
                ['name' => $softwareName],
                ['is_active' => true],
                ['created_at' => now()],
                ['updates_at' => now()]
            );
        }
    }
}
