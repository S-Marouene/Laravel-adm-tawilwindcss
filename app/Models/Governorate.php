<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Governorate extends Model
{
    protected $fillable = ['id', 'name_ar', 'name_fr', 'name_en'];

    // Prevent auto-increment so our explicit 1–24 IDs are preserved
    public $incrementing = false;

    protected $casts = [
        'id' => 'integer',
    ];

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    public function delegations(): HasMany
    {
        return $this->hasMany(Delegation::class, 'governorate_id');
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

    /**
     * All governorates keyed by id => localized name, for use in select dropdowns.
     * Uses the accessor (computed from name_ar/fr/en) — must load full rows first.
     */
    public static function forSelect(): array
    {
        $locale = app()->getLocale();
        $col    = match ($locale) {
            'fr'    => 'name_fr',
            'en'    => 'name_en',
            default => 'name_ar',
        };

        return static::orderBy('id')
            ->pluck($col, 'id')
            ->toArray();
    }

    /**
     * Full delegation map: { governorate_id: [ { id, name }, ... ] }
     * Uses the current locale for the display name.
     * The JS cascade stores the integer ID as the option value — locale-safe.
     */
    public static function delegationsMap(): array
    {
        $locale = app()->getLocale();
        $col    = match ($locale) {
            'fr'    => 'name_fr',
            'en'    => 'name_en',
            default => 'name_ar',
        };

        return Delegation::orderBy('governorate_id')
            ->orderBy('id')
            ->get()
            ->groupBy('governorate_id')
            ->map(fn (Collection $rows) => $rows->map(fn (Delegation $d) => [
                'id'   => $d->id,
                'name' => $d->{$col},
            ])->values()->toArray())
            ->toArray();
    }
}
