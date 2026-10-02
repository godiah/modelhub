<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Money from a job's escrow returned to the client, recorded by staff (who then send it by M-Pesa). */
class EscrowRefund extends Model
{
    protected $fillable = ['engagement_id', 'user_id', 'amount_minor', 'currency', 'msisdn', 'funding_receipt', 'staff_id', 'note'];

    protected function casts(): array
    {
        return ['amount_minor' => 'integer'];
    }

    public function engagement()
    {
        return $this->belongsTo(JobEngagement::class, 'engagement_id');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
