<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\FlashAlertHelper;
use App\Http\Controllers\Controller;
use App\Support\Settings\PlatformSettings;
use App\Support\Settings\SecuritySettings;
use Illuminate\Http\Request;

/** Platform settings, for Super admins. Security is the first page; further groups get their own page beside it. */
class AdminSettingsController extends Controller
{
    public function security()
    {
        return view('admin.settings.security', ['sections' => SecuritySettings::sections(), 'definitions' => SecuritySettings::definitions(), 'values' => PlatformSettings::all()]);
    }

    public function updateSecurity(Request $request)
    {
        $definitions = SecuritySettings::definitions();
        $fields = collect($definitions)->mapWithKeys(fn ($definition, $key) => [str($key)->after('security.')->toString() => $key]);

        $validated = $request->validate(
            $fields->mapWithKeys(fn ($key, $field) => ["settings.{$field}" => $definitions[$key]['type'] === 'bool' ? ['nullable', 'boolean'] : PlatformSettings::rules($key)])->all(),
            [],
            $fields->mapWithKeys(fn ($key, $field) => ["settings.{$field}" => strtolower($definitions[$key]['label'])])->all(),
        )['settings'] ?? [];

        // An unticked checkbox sends nothing, which here means off
        $values = $fields->mapWithKeys(fn ($key, $field) => [$key => $definitions[$key]['type'] === 'bool' ? (bool) ($validated[$field] ?? false) : $validated[$field]])->all();

        $changes = PlatformSettings::update($values, $request->user());

        return back()->with(FlashAlertHelper::success($changes === [] ? 'Nothing changed' : trans_choice('Saved :count change|Saved :count changes', count($changes), ['count' => count($changes)])));
    }
}
