<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'label', 'description', 'is_public'];

    /**
     * Get a setting value by key.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        $value = $setting->value;

        return match ($setting->type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Set a setting value by key (create or update).
     */
    public static function setValue(string $key, mixed $value, ?string $type = null): self
    {
        $data = ['value' => is_array($value) ? json_encode($value) : (string) $value];

        if ($type) {
            $data['type'] = $type;
        }

        return static::updateOrCreate(
            ['key' => $key],
            $data
        );
    }

    /**
     * Get all settings grouped by their group.
     */
    public static function getGrouped(): \Illuminate\Support\Collection
    {
        return static::orderBy('group')->orderBy('id')->get()->groupBy('group');
    }

    /**
     * Get all public settings (useful for frontend APIs).
     */
    public static function getPublic(): array
    {
        return static::where('is_public', true)
            ->get()
            ->pluck('value', 'key')
            ->toArray();
    }
}
