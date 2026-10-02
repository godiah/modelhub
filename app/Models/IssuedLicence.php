<?php

namespace App\Models;

use App\Enums\LicenceTier;
use Illuminate\Database\Eloquent\Model;

/** What a buyer holds after buying a model: the legal record of the licence, with everything copied from the moment it was issued. */
class IssuedLicence extends Model
{
    protected $fillable = [
        'key', 'purchase_id', 'user_id', 'product_id', 'tier', 'terms_version', 'terms', 'licensee_name', 'product_title', 'seller_name',
        'price_minor', 'currency', 'issued_at', 'revoked_at', 'revoked_reason',
    ];

    protected function casts(): array
    {
        return ['tier' => LicenceTier::class, 'terms' => 'array', 'price_minor' => 'integer', 'issued_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'key';
    }

    public function holder()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }

    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at');
    }
}
