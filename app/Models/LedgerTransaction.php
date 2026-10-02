<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/** One business event in the ledger: a sale, a release, a payout. Always balanced, and never changed or removed once posted. */
class LedgerTransaction extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['type', 'idempotency_key', 'reference_type', 'reference_id', 'description', 'meta', 'staff_id', 'occurred_at'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'occurred_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Ledger transactions cannot be changed. Post an opposite transaction instead.'));
        static::deleting(fn () => throw new LogicException('Ledger transactions cannot be deleted. Post an opposite transaction instead.'));
    }

    public function entries()
    {
        return $this->hasMany(LedgerEntry::class, 'transaction_id');
    }

    public function reference()
    {
        return $this->morphTo();
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
