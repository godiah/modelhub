<?php

namespace App\Enums;

enum GeometryType: string
{
    case PolygonMesh = 'polygon_mesh';
    case Nurbs = 'nurbs';
    case Subdivision = 'subdivision';

    public function label(): string
    {
        return match ($this) {
            self::PolygonMesh => 'Polygon mesh',
            self::Nurbs => 'NURBS',
            self::Subdivision => 'Subdivision',
        };
    }
}
