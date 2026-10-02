<?php

namespace App\Models;

use App\Enums\PayoutStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** A member withdrawing earnings to M-Pesa. Moved through its states only by PayoutService, which keeps the ledger in step. */
class Payout extends Model
{
    protected $fillable = [
        'reference', 'user_id', 'amount_minor', 'fee_minor', 'net_minor', 'currency', 'msisdn', 'status', 'gateway', 'gateway_reference', 'receipt',
        'approved_by', 'approved_at', 'completed_at', 'failure_reason',
    ];

    protected function casts(): array
    {
        return ['status' => PayoutStatus::class, 'amount_minor' => 'integer', 'fee_minor' => 'integer', 'net_minor' => 'integer', 'approved_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(Staff::class, 'approved_by');
    }

    public static function newReference(): string
    {
        do {
            $reference = 'PO'.Str::upper(Str::random(10));
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }
}
