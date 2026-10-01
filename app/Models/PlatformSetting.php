<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One platform setting a Super admin has changed from its default. Read through PlatformSettings, never directly. */
class PlatformSetting extends Model
{
    protected $fillable = ['key', 'value', 'updated_by'];

    protected function casts(): array
    {
        return ['value' => 'json'];
    }
}
