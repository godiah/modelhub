<?php

namespace App\Models;

use App\Enums\LedgerAccountKind;
use Illuminate\Database\Eloquent\Model;

/** A place money can sit. Its balance is never stored: it is always worked out from its entries. */
class LedgerAccount extends Model
{
    protected $fillable = ['code', 'name', 'kind', 'user_id', 'currency', 'allow_negative'];

    protected function casts(): array
    {
        return ['kind' => LedgerAccountKind::class, 'allow_negative' => 'boolean'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function entries()
    {
        return $this->hasMany(LedgerEntry::class, 'account_id');
    }

    /** What the account holds, in minor units: positive when it holds money, whichever kind of account it is. */
    public function balanceMinor(): int
    {
        $net = (int) $this->entries()->selectRaw("coalesce(sum(case when direction = 'debit' then amount_minor else -amount_minor end), 0) as net")->value('net');

        return $this->kind->growsWith() === 'debit' ? $net : -$net;
    }
}
