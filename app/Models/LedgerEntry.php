<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/** One line of a ledger transaction: an amount debited or credited to an account. Append-only. */
class LedgerEntry extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['transaction_id', 'account_id', 'direction', 'amount_minor', 'currency'];

    protected function casts(): array
    {
        return ['amount_minor' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Ledger entries cannot be changed. Post an opposite transaction instead.'));
        static::deleting(fn () => throw new LogicException('Ledger entries cannot be deleted. Post an opposite transaction instead.'));
    }

    public function transaction()
    {
        return $this->belongsTo(LedgerTransaction::class, 'transaction_id');
    }

    public function account()
    {
        return $this->belongsTo(LedgerAccount::class, 'account_id');
    }
}
