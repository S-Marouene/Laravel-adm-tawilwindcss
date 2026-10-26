<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delegation extends Model
{
    protected $fillable = ['governorate_id', 'name_ar', 'name_fr', 'name_en'];

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class, 'governorate_id');
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Return the localized name for the current app locale.
     */
    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();

        return match ($locale) {
            'fr'    => $this->name_fr,
            'en'    => $this->name_en,
            default => $this->name_ar,
        };
    }
}
