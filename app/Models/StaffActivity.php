<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One line of the staff activity log: who did what, to what, and when. Written by App\Support\Staff\StaffAudit. */
class StaffActivity extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'staff_activity';

    protected $fillable = ['staff_id', 'action', 'subject_type', 'subject_id', 'summary', 'details', 'ip_address'];

    protected function casts(): array
    {
        return ['details' => 'array', 'created_at' => 'datetime'];
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
