<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ThemeSetting;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ThemeSettingsController extends Controller
{
    public function index()
    {
        $themeSettings = ThemeSetting::getGrouped();

        return view('admin.theme-settings.index', compact('themeSettings'));
    }

    public function update(Request $request)
    {
        ThemeSetting::ensureDefaults();

        $settings = ThemeSetting::all()->keyBy('key');
        $rules = [];

        foreach ($settings as $setting) {
            $rules[$setting->key] = $this->validationRuleFor($setting);
        }

        $validated = $request->validate($rules);

        foreach ($settings as $setting) {
            if (array_key_exists($setting->key, $validated)) {
                $setting->update(['value' => $validated[$setting->key]]);
            }
        }

        cache()->forget('theme_settings');

        ActivityLogger::updated('theme settings', new ThemeSetting(), [
            'updated_keys' => array_keys($validated),
        ]);

        return redirect()->route('admin.theme-settings.index')
            ->with('success', __('admin.theme_settings_updated'));
    }

    private function validationRuleFor(ThemeSetting $setting): array
    {
        return match ($setting->type) {
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'number' => ['required', 'integer', 'min:10', 'max:28'],
            'select' => ['required', Rule::in($setting->options ?? [])],
            default => ['nullable', 'string', 'max:255'],
        };
    }
}
