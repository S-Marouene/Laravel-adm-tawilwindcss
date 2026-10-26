<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuildingActivity extends Model
{
    protected $fillable = ['building_type_id', 'name_ar', 'name_fr', 'name_en', 'sort_order'];

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    public function buildingType(): BelongsTo
    {
        return $this->belongsTo(BuildingType::class, 'building_type_id');
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
}
