<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A private note staff keep on a member ("warned about X on 3 Oct"). Never shown to the member. */
class MemberNote extends Model
{
    protected $fillable = ['user_id', 'staff_id', 'body'];

    public function member()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function author()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}
