<?php

// FlashAlertHelper builds the flash payload consumed by partials/flash-messages.blade.php:
// a flat session key (success/error/info/warning) plus a richer 'alert' array carrying a
// specific toast title. Single source of truth for a shape that used to be hand-built
// independently at ~37 call sites across controllers and services.

namespace App\Helpers;

class FlashAlertHelper
{
    public static function success(string $title, ?string $text = null): array
    {
        return self::make('success', $title, $text);
    }

    public static function error(string $title, ?string $text = null): array
    {
        return self::make('error', $title, $text);
    }

    public static function info(string $title, ?string $text = null): array
    {
        return self::make('info', $title, $text);
    }

    public static function warning(string $title, ?string $text = null): array
    {
        return self::make('warning', $title, $text);
    }

    // For call sites where the type itself is dynamic (e.g. success or info depending
    // on outcome) — prefer success()/error()/info()/warning() when the type is known upfront.
    public static function make(string $type, string $title, ?string $text = null): array
    {
        $text ??= $title;

        return [
            $type => $text,
            'alert' => [
                'type' => $type,
                'title' => $title,
                'text' => $text,
            ],
        ];
    }
}
