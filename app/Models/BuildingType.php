<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BuildingType extends Model
{
    protected $fillable = ['name_ar', 'name_fr', 'name_en', 'has_activities', 'sort_order'];

    protected $casts = [
        'has_activities' => 'boolean',
    ];

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    public function activities(): HasMany
    {
        return $this->hasMany(BuildingActivity::class, 'building_type_id')->orderBy('sort_order');
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /** Localized name for the current locale. */
    public function getNameAttribute(): string
    {
        return match (app()->getLocale()) {
            'fr'    => $this->name_fr,
            'en'    => $this->name_en,
            default => $this->name_ar,
        };
    }

    /** All types as [id => name] for select dropdowns. */
    public static function forSelect(): array
    {
        $col = match (app()->getLocale()) {
            'fr'    => 'name_fr',
            'en'    => 'name_en',
            default => 'name_ar',
        };

        return static::orderBy('sort_order')
            ->pluck($col, 'id')
            ->toArray();
    }

    /**
     * Full activities map: { typeId: { has_activities: bool, activities: [{ id, name }] } }
     * JSON-encoded into the view for the client-side cascade.
     */
    public static function activitiesMap(): array
    {
        $locale = app()->getLocale();
        $col    = match ($locale) {
            'fr'    => 'name_fr',
            'en'    => 'name_en',
            default => 'name_ar',
        };

        $map = [];
        static::with(['activities' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->each(function (BuildingType $type) use (&$map, $col) {
                $map[$type->id] = [
                    'has_activities' => $type->has_activities,
                    'activities'     => $type->activities
                        ->map(fn (BuildingActivity $a) => [
                            'id'   => $a->id,
                            'name' => $a->{$col},
                        ])
                        ->values()
                        ->toArray(),
                ];
            });

        return $map;
    }
}
