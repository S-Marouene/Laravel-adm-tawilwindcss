<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ThemeSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'options',
        'sort_order',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'options' => 'array',
    ];

    public static function getGrouped(): \Illuminate\Support\Collection
    {
        static::ensureDefaults();

        return static::orderBy('group')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('group');
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        try {
            if (! Schema::hasTable('theme_settings')) {
                return $default;
            }

            $setting = static::where('key', $key)->first();
        } catch (\Throwable) {
            return $default;
        }

        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'number' => (int) $setting->value,
            default => $setting->value,
        };
    }

    public static function getCssValues(): array
    {
        $defaults = collect(static::defaults())->pluck('value', 'key')->toArray();

        try {
            if (! Schema::hasTable('theme_settings')) {
                return $defaults;
            }

            return array_merge(
                $defaults,
                static::pluck('value', 'key')->toArray()
            );
        } catch (\Throwable) {
            return $defaults;
        }
    }

    public static function ensureDefaults(): void
    {
        try {
            if (! Schema::hasTable('theme_settings')) {
                return;
            }

            foreach (static::defaults() as $setting) {
                static::firstOrCreate(
                    ['key' => $setting['key']],
                    $setting
                );
            }
        } catch (\Throwable) {
            // Theme controls should never block the app during setup.
        }
    }

    public static function defaults(): array
    {
        return [
            [
                'key' => 'primary_color',
                'value' => '#6B3FAF',
                'type' => 'color',
                'group' => 'colors',
                'label' => 'Primary color',
                'description' => 'Main action, active navigation, and focus color.',
                'sort_order' => 10,
                'is_public' => true,
            ],
            [
                'key' => 'accent_color',
                'value' => '#0891B2',
                'type' => 'color',
                'group' => 'colors',
                'label' => 'Accent color',
                'description' => 'Secondary highlights, gradients, and decorative accents.',
                'sort_order' => 20,
                'is_public' => true,
            ],
            [
                'key' => 'sidebar_color',
                'value' => '#111C43',
                'type' => 'color',
                'group' => 'colors',
                'label' => 'Admin sidebar color',
                'description' => 'Base background color for the admin sidebar.',
                'sort_order' => 30,
                'is_public' => true,
            ],
            [
                'key' => 'page_background',
                'value' => '#F5F6FA',
                'type' => 'color',
                'group' => 'colors',
                'label' => 'Page background',
                'description' => 'Default light-mode page background.',
                'sort_order' => 40,
                'is_public' => true,
            ],
            [
                'key' => 'font_family',
                'value' => 'Inter',
                'type' => 'select',
                'group' => 'typography',
                'label' => 'Font family',
                'description' => 'Primary interface font for non-Arabic pages.',
                'options' => ['Inter', 'Arial', 'Georgia', 'Tahoma', 'Verdana'],
                'sort_order' => 10,
                'is_public' => true,
            ],
            [
                'key' => 'base_font_size',
                'value' => '16',
                'type' => 'number',
                'group' => 'typography',
                'label' => 'Base font size',
                'description' => 'Root font size in pixels.',
                'sort_order' => 20,
                'is_public' => true,
            ],
            [
                'key' => 'heading_style',
                'value' => 'balanced',
                'type' => 'select',
                'group' => 'typography',
                'label' => 'Heading style',
                'description' => 'Controls heading weight and spacing.',
                'options' => ['balanced', 'strong', 'light'],
                'sort_order' => 30,
                'is_public' => true,
            ],
            [
                'key' => 'interface_density',
                'value' => 'comfortable',
                'type' => 'select',
                'group' => 'layout',
                'label' => 'Interface density',
                'description' => 'Adjusts common form and table spacing.',
                'options' => ['compact', 'comfortable', 'spacious'],
                'sort_order' => 10,
                'is_public' => true,
            ],
            [
                'key' => 'border_radius',
                'value' => '12',
                'type' => 'number',
                'group' => 'layout',
                'label' => 'Border radius',
                'description' => 'Default component corner radius in pixels.',
                'sort_order' => 20,
                'is_public' => true,
            ],
            [
                'key' => 'button_style',
                'value' => 'gradient',
                'type' => 'select',
                'group' => 'components',
                'label' => 'Button style',
                'description' => 'Visual style for primary action buttons.',
                'options' => ['gradient', 'solid', 'outline'],
                'sort_order' => 10,
                'is_public' => true,
            ],
            [
                'key' => 'card_shadow',
                'value' => 'soft',
                'type' => 'select',
                'group' => 'components',
                'label' => 'Card shadow',
                'description' => 'Elevation used on cards and form surfaces.',
                'options' => ['none', 'soft', 'deep'],
                'sort_order' => 20,
                'is_public' => true,
            ],
        ];
    }
}
