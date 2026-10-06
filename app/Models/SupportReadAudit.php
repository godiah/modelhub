<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One read by the support assistant of a member's own records. Who and what endpoint and how it ended; never what was read. */
class SupportReadAudit extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['request_id', 'principal', 'user_id', 'endpoint', 'status', 'outcome'];
}
