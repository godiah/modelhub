<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\FlashAlertHelper;
use App\Http\Controllers\Controller;
use App\Support\Settings\FeeSettings;
use App\Support\Settings\PlatformSettings;
use App\Support\Settings\SecuritySettings;
use Illuminate\Http\Request;

/** Platform settings, for Super admins. One page per group: Security, then Fees and payments. */
class AdminSettingsController extends Controller
{
    /** group => [settings class, the key prefix, the page title] */
    public const GROUPS = [
        'security' => [SecuritySettings::class, 'security', 'Security'],
        'fees' => [FeeSettings::class, 'fees', 'Fees and payments'],
    ];

    public function security()
    {
        return $this->show('security');
    }

    public function updateSecurity(Request $request)
    {
        return $this->save($request, 'security');
    }

    public function fees()
    {
        return $this->show('fees');
    }

    public function updateFees(Request $request)
    {
        return $this->save($request, 'fees');
    }

    private function show(string $group)
    {
        [$class, $prefix, $title] = self::GROUPS[$group];

        return view('admin.settings.'.$group, ['group' => $group, 'groups' => self::GROUPS, 'prefix' => $prefix, 'sections' => $class::sections(), 'definitions' => $class::definitions(), 'values' => PlatformSettings::all()]);
    }

    private function save(Request $request, string $group)
    {
        [$class, $prefix] = self::GROUPS[$group];
        $definitions = $class::definitions();
        $fields = collect($definitions)->mapWithKeys(fn ($definition, $key) => [str($key)->after($prefix.'.')->toString() => $key]);

        $validated = $request->validate(
            $fields->mapWithKeys(fn ($key, $field) => ["settings.{$field}" => $definitions[$key]['type'] === 'bool' ? ['nullable', 'boolean'] : PlatformSettings::rules($key)])->all(),
            [],
            $fields->mapWithKeys(fn ($key, $field) => ["settings.{$field}" => strtolower($definitions[$key]['label'])])->all(),
        )['settings'] ?? [];

        // An unticked checkbox sends nothing, which here means off
        $values = $fields->mapWithKeys(fn ($key, $field) => [$key => $definitions[$key]['type'] === 'bool' ? (bool) ($validated[$field] ?? false) : $validated[$field]])->all();

        $changes = PlatformSettings::update($values, $request->user(), $prefix);

        return back()->with(FlashAlertHelper::success($changes === [] ? 'Nothing changed' : trans_choice('Saved :count change|Saved :count changes', count($changes), ['count' => count($changes)])));
    }
}
