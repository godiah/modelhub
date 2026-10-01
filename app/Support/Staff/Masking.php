<?php

namespace App\Support\Staff;

/** Hides most of an email address or phone number unless the staff member is allowed to see contact details. */
final class Masking
{
    public static function email(?string $email, bool $reveal): string
    {
        if (blank($email)) {
            return '—';
        }

        if ($reveal || ! str_contains($email, '@')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);

        return mb_substr($local, 0, 1).str_repeat('•', max(2, min(6, mb_strlen($local) - 1))).'@'.$domain;
    }

    public static function phone(?string $phone, bool $reveal): string
    {
        if (blank($phone)) {
            return '—';
        }

        return $reveal ? $phone : '•••• '.mb_substr(preg_replace('/\D+/', '', $phone), -3);
    }
}
