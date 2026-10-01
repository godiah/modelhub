<?php

namespace App\Support\Settings;

use App\Models\PlatformSetting;
use App\Models\Staff;
use App\Support\Staff\StaffAudit;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

/**
 * Platform-wide settings a Super admin controls. The settings and their defaults are declared in code
 * (SecuritySettings); only values that differ from the default are stored. Reads are cached, and every change is written to
 * the staff activity log with the old and new value.
 *
 *   PlatformSettings::get('security.otp_members_required')   // bool
 */
final class PlatformSettings
{
    private const CACHE_KEY = 'platform-settings';

    /** @return array<string, array<string, mixed>> */
    public static function definitions(): array
    {
        return SecuritySettings::definitions();
    }

    public static function get(string $key): mixed
    {
        $definition = self::definitions()[$key] ?? throw new InvalidArgumentException("Unknown platform setting [{$key}].");
        $stored = self::stored();

        return array_key_exists($key, $stored) ? self::cast($definition, $stored[$key]) : $definition['default'];
    }

    public static function bool(string $key): bool
    {
        return (bool) self::get($key);
    }

    public static function int(string $key): int
    {
        return (int) self::get($key);
    }

    /** Every setting with its current value. @return array<string, mixed> */
    public static function all(): array
    {
        return collect(self::definitions())->map(fn ($definition, $key) => self::get($key))->all();
    }

    /** Validation rules for a setting's value, from its definition. @return list<string> */
    public static function rules(string $key): array
    {
        $definition = self::definitions()[$key];

        return $definition['type'] === 'bool'
            ? ['boolean']
            : ['required', 'integer', 'min:'.($definition['min'] ?? 0), 'max:'.($definition['max'] ?? PHP_INT_MAX)];
    }

    /**
     * Save new values (only the ones that differ are written) and log what changed.
     *
     * @param  array<string, mixed>  $values  setting key => new value
     * @return array<string, array{label: string, from: mixed, to: mixed}> what actually changed
     */
    public static function update(array $values, Staff $by, string $area = 'security'): array
    {
        $changes = [];

        foreach ($values as $key => $value) {
            $definition = self::definitions()[$key] ?? throw new InvalidArgumentException("Unknown platform setting [{$key}].");
            $value = self::cast($definition, $value);
            $current = self::get($key);

            if ($value === $current) {
                continue;
            }

            PlatformSetting::updateOrCreate(['key' => $key], ['value' => $value, 'updated_by' => $by->id]);
            $changes[$key] = ['label' => $definition['label'], 'from' => $current, 'to' => $value];
        }

        if ($changes !== []) {
            Cache::forget(self::CACHE_KEY);

            StaffAudit::log(
                "settings.{$area}-updated",
                'Changed '.trans_choice(':count security setting|:count security settings', count($changes), ['count' => count($changes)]).': '.collect($changes)->map(fn ($c) => $c['label'].' ('.self::display($c['from']).' to '.self::display($c['to']).')')->implode(', '),
                details: ['changes' => $changes],
                staffId: $by->id,
            );
        }

        return $changes;
    }

    private static function display(mixed $value): string
    {
        return is_bool($value) ? ($value ? 'on' : 'off') : (string) $value;
    }

    /** @return array<string, mixed> */
    private static function stored(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => PlatformSetting::query()->pluck('value', 'key')->all());
    }

    private static function cast(array $definition, mixed $value): mixed
    {
        return $definition['type'] === 'bool' ? filter_var($value, FILTER_VALIDATE_BOOLEAN) : (int) $value;
    }
}
