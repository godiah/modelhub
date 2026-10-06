<?php

namespace App\Http\Resources\Support;

use App\Models\IssuedLicence;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * What the support assistant may see of a licence the member holds. The model's title is written by the seller, so it is shortened, and it only ever
 * appears in a card drawn as plain text (never in stored message text, which later prompts can read). Left out: the licence key, the licensee name,
 * the seller's name, the terms text.
 *
 * @mixin IssuedLicence
 */
class LicenceResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'tier' => $this->tier->value,
            'tier_label' => $this->tier->label(),
            'title' => mb_substr((string) ($this->product_title ?: $this->product?->title), 0, 80),
            'status' => $this->isActive() ? 'active' : 'ended',
            'issued_at' => $this->issued_at?->toIso8601String(),
            'ended_at' => $this->revoked_at?->toIso8601String(),
            'reason' => $this->isActive() ? null : mb_substr((string) $this->revoked_reason, 0, 120),
            'price_display' => Money::formatMinor($this->price_minor, 0),
        ];
    }
}
