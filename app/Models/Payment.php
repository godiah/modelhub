<?php

namespace App\Models;

use App\Enums\LicenceTier;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Someone paying by M-Pesa: a buyer paying for a model (purpose model_sale) or a client funding a job's escrow (purpose escrow). Settled once, by
 * PaymentService, which gives the buyer their licence, or opens the job for work, and records it in the ledger.
 */
class Payment extends Model
{
    public const PURPOSE_SALE = 'model_sale';

    public const PURPOSE_ESCROW = 'escrow';

    protected $fillable = [
        'reference', 'purpose', 'user_id', 'seller_id', 'product_id', 'engagement_id', 'tier', 'amount_minor', 'received_minor', 'currency', 'msisdn', 'status', 'gateway', 'gateway_reference', 'receipt',
        'commission_rate', 'commission_minor', 'seller_share_minor', 'hold_days', 'failure_reason', 'purchase_id', 'expires_at', 'completed_at', 'release_at', 'released_at', 'refunded_at', 'refunded_by', 'refund_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class, 'tier' => LicenceTier::class, 'amount_minor' => 'integer', 'commission_minor' => 'integer', 'seller_share_minor' => 'integer',
            'commission_rate' => 'decimal:4', 'hold_days' => 'integer', 'expires_at' => 'datetime', 'completed_at' => 'datetime', 'release_at' => 'datetime', 'released_at' => 'datetime', 'refunded_at' => 'datetime', 'received_minor' => 'integer',
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

    public function refunder()
    {
        return $this->belongsTo(Staff::class, 'refunded_by');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function engagement()
    {
        return $this->belongsTo(JobEngagement::class, 'engagement_id');
    }

    /** What was paid for, for lists and headings: the model's title, or the job's. */
    public function subjectTitle(): string
    {
        return $this->isEscrow()
            ? ($this->engagement?->application?->job?->title ?? __('A deleted job'))
            : ($this->product?->title ?? __('A deleted model'));
    }

    /** What kind of payment it is, in a few words: "Standard licence", or "Job escrow". */
    public function subjectKind(): string
    {
        return $this->isEscrow() ? __('Job escrow') : __(':tier licence', ['tier' => $this->tier?->label()]);
    }

    public function isEscrow(): bool
    {
        return $this->purpose === self::PURPOSE_ESCROW;
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
