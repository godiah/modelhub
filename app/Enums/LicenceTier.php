<?php

namespace App\Enums;

/**
 * The licences a model is sold under, and what each one allows. This is the one place the wording lives: the public licence page, the
 * product page and the licence a buyer holds all read it. The wording is ours and provisional until a lawyer has reviewed it (see
 * config/legal.php); change TERMS_VERSION whenever it changes, because every licence records the version it was issued under.
 */
enum LicenceTier: string
{
    case Standard = 'standard';
    case Extended = 'extended';

    /** The version of the wording below. Each issued licence keeps the version it was issued under. */
    public const TERMS_VERSION = '2026-10';

    /** The wording of this licence as it reads now, to be copied onto a licence when it is issued. @return array{permitted: list<string>, restrictions: list<string>, general: list<string>} */
    public function terms(): array
    {
        return ['permitted' => $this->permitted(), 'restrictions' => self::restrictions(), 'general' => self::general()];
    }

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Standard',
            self::Extended => 'Extended',
        };
    }

    /** One line for a product page. */
    public function summary(): string
    {
        return match ($this) {
            self::Standard => 'For one end product, by one licensee.',
            self::Extended => 'For any number of end products, a small team, and products you sell.',
        };
    }

    /** What the licensee may do. @return list<string> */
    public function permitted(): array
    {
        return match ($this) {
            self::Standard => [
                'Use the model in one end product: one game, film, app, website, visualisation or advertising campaign.',
                'Use it commercially, including for a client you are making that end product for.',
                'Modify, retexture, rig, combine and render the model, and publish images and video of it.',
                'Keep using it for as long as you like: the licence does not expire.',
            ],
            self::Extended => [
                'Use the model in any number of end products, with no limit on how many.',
                'Share it with up to 10 members of the licensee\'s own team or company who work on those products.',
                'Use it in products you make and sell from the model, such as merchandise or 3D prints.',
                'Everything in the Standard licence, and no expiry.',
            ],
        };
    }

    /** What no licence allows. @return list<string> */
    public static function restrictions(): array
    {
        return [
            'Resell, share, sub-license or give away the model or its files, original or changed, as a 3D model, texture or asset. That includes asset packs, stock libraries and other marketplaces.',
            'Make the files available in a form others can take and reuse. An end product that has to contain the model, such as a game, must keep it in a compiled or protected form.',
            'Transfer the licence to someone else, or let people outside the licence use the model.',
            'Claim that you made the model. Crediting the seller is not required, but they will appreciate it.',
        ];
    }

    /** The terms that apply to every licence. @return list<string> */
    public static function general(): array
    {
        return [
            'The seller keeps the copyright in the model. A licence lets you use it as described here; it does not sell you the model.',
            'The licence is non-exclusive and worldwide, and covers the files you bought. It is held by the person named on it.',
            'It ends if the purchase is refunded, or if these terms are broken, and the files must then no longer be used in new work.',
            'The licence page of a purchase is the record of what was bought, the price paid and which version of these terms applies.',
        ];
    }
}
