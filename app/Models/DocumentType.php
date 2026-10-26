<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    protected $fillable = ['name_ar', 'name_fr', 'name_en', 'is_conditional', 'sort_order'];

    protected $casts = [
        'is_conditional' => 'boolean',
        'sort_order'     => 'integer',
    ];

    // -----------------------------------------------------------------------

    public function dossierDocuments(): HasMany
    {
        return $this->hasMany(DossierDocument::class);
    }

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
}
