<?php

namespace App\Services\Support\Tickets;

use App\Models\IssuedLicence;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\User;

/**
 * The records a ticket is about, checked against the member who files it. A reference the member does not own is dropped, not trusted: the
 * assistant's evidence and the member's own words can name anything, and staff must only ever see links to the member's own records.
 */
final class EntityRefs
{
    public const MAX = 5;

    /**
     * @param  list<array{type?: mixed, reference?: mixed}>  $claimed
     * @return list<array{type: string, reference: string, model: Payment|Payout|IssuedLicence}>
     */
    public static function validated(User $member, array $claimed): array
    {
        $found = [];

        foreach (array_slice($claimed, 0, self::MAX * 2) as $ref) {
            $type = is_string($ref['type'] ?? null) ? $ref['type'] : null;
            $reference = is_string($ref['reference'] ?? null) ? mb_substr($ref['reference'], 0, 32) : null;

            if ($type === null || $reference === null) {
                continue;
            }

            $model = match ($type) {
                'payment' => Payment::where('user_id', $member->getKey())->where('reference', $reference)->first(),
                'withdrawal' => Payout::where('user_id', $member->getKey())->where('reference', $reference)->first(),
                'licence' => IssuedLicence::where('user_id', $member->getKey())->where('key', $reference)->first(),
                default => null,
            };

            if ($model !== null && ! isset($found["{$type}:{$reference}"])) {
                $found["{$type}:{$reference}"] = ['type' => $type, 'reference' => $reference, 'model' => $model];
            }

            if (count($found) >= self::MAX) {
                break;
            }
        }

        return array_values($found);
    }

    /** The compact form stored on the ticket: ["payment:MH...", ...]. */
    public static function keys(array $validated): array
    {
        return array_map(fn (array $ref) => $ref['type'].':'.$ref['reference'], $validated);
    }
}
