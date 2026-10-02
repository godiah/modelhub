<?php

namespace App\Models;

use App\Enums\LicenceTier;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** A buyer paying for a model by M-Pesa. Settled once, by PaymentService, which gives them their licence and records the sale in the ledger. */
class Payment extends Model
{
    protected $fillable = [
        'reference', 'user_id', 'seller_id', 'product_id', 'tier', 'amount_minor', 'currency', 'msisdn', 'status', 'gateway', 'gateway_reference', 'receipt',
        'commission_rate', 'commission_minor', 'seller_share_minor', 'hold_days', 'failure_reason', 'purchase_id', 'expires_at', 'completed_at', 'release_at', 'released_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class, 'tier' => LicenceTier::class, 'amount_minor' => 'integer', 'commission_minor' => 'integer', 'seller_share_minor' => 'integer',
            'commission_rate' => 'decimal:4', 'hold_days' => 'integer', 'expires_at' => 'datetime', 'completed_at' => 'datetime', 'release_at' => 'datetime', 'released_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::Pending;
    }

    public function hasExpired(): bool
    {
        return $this->isPending() && $this->expires_at->isPast();
    }

    /** A short reference no longer than M-Pesa allows (12 characters), unique among payments. */
    public static function newReference(): string
    {
        do {
            $reference = 'MH'.Str::upper(Str::random(10));
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }
}
