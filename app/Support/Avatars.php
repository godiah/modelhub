<?php

namespace App\Support;

/**
 * The avatar catalogue: what a member or store can pick, a random pick for new accounts, and the file behind a pick.
 *
 * Two kinds exist, "people" (members) and "stores", each with its own styles. A pick is stored as "style/seed".
 * Anything missing or no longer in the catalogue falls back to a pick derived from a stable value (the member's or
 * store's id), so an avatar is always shown and never changes between page loads.
 */
final class Avatars
{
    public const PEOPLE = 'people';

    public const STORES = 'stores';

    /**
     * Every style of a kind with its seeds, shaped for the picker.
     *
     * @return list<array{key: string, label: string, seeds: list<string>}>
     */
    public static function catalogue(string $kind): array
    {
        $seeds = config("avatars.{$kind}.seeds", []);

        return array_map(fn (array $style) => $style + ['seeds' => $seeds], config("avatars.{$kind}.styles", []));
    }

    /** Every valid pick of a kind, as "style/seed". */
    public static function keys(string $kind): array
    {
        $keys = [];

        foreach (config("avatars.{$kind}.styles", []) as $style) {
            foreach (config("avatars.{$kind}.seeds", []) as $seed) {
                $keys[] = "{$style['key']}/{$seed}";
            }
        }

        return $keys;
    }

    public static function isValid(string $kind, mixed $key): bool
    {
        if (! is_string($key) || substr_count($key, '/') !== 1) {
            return false;
        }

        [$style, $seed] = explode('/', $key);

        return in_array($seed, config("avatars.{$kind}.seeds", []), true)
            && in_array($style, array_column(config("avatars.{$kind}.styles", []), 'key'), true);
    }

    /** A random pick: what every new member and store starts with. */
    public static function random(string $kind): string
    {
        $styles = config("avatars.{$kind}.styles", []);
        $seeds = config("avatars.{$kind}.seeds", []);

        return $styles[random_int(0, count($styles) - 1)]['key'].'/'.$seeds[random_int(0, count($seeds) - 1)];
    }

    /** The same pick every time for the same value, for when nothing valid is stored. */
    public static function fallback(string $kind, int|string $value): string
    {
        $styles = config("avatars.{$kind}.styles", []);
        $seeds = config("avatars.{$kind}.seeds", []);
        $hash = crc32((string) $value);

        return $styles[$hash % count($styles)]['key'].'/'.$seeds[intdiv($hash, count($styles)) % count($seeds)];
    }

    /** The picture for a stored pick, or the stable fallback for the given value. */
    public static function url(string $kind, ?string $key, int|string $fallbackValue = 0): string
    {
        $key = self::isValid($kind, $key) ? $key : self::fallback($kind, $fallbackValue);

        return asset(config('avatars.path')."/{$kind}/{$key}.svg");
    }
}
