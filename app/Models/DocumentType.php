<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    // Which service's checklist the document belongs to
    public const SERVICE_PREVENTION = 'prevention';
    public const SERVICE_PREVENTIVE_PRESENCE = 'preventive-presence';
    public const SERVICE_RESCUE_CERTIFICATE = 'rescue-certificate';
    public const SERVICE_WORKS_NOTIFICATION = 'works-notification';

    protected $fillable = ['name_ar', 'name_fr', 'name_en', 'service_key', 'is_conditional', 'sort_order'];

    protected $casts = [
        'is_conditional' => 'boolean',
        'sort_order'     => 'integer',
    ];

    // -----------------------------------------------------------------------

    public function dossierDocuments(): HasMany
    {
        return $this->hasMany(DossierDocument::class);
    }

    public function presenceDocuments(): HasMany
    {
        return $this->hasMany(PresenceDocument::class);
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

    /** Scope types belonging to a given service checklist. */
    public function scopeService($query, string $serviceKey)
    {
        return $query->where('service_key', $serviceKey);
    }

    /** All types of a service as [id => name] for select dropdowns. */
    public static function forSelect(string $serviceKey = null): array
    {
        $col = match (app()->getLocale()) {
            'fr'    => 'name_fr',
            'en'    => 'name_en',
            default => 'name_ar',
        };

        $query = static::orderBy('sort_order');
        if ($serviceKey !== null) {
            $query->where('service_key', $serviceKey);
        }

        return $query->pluck($col, 'id')
            ->toArray();
    }
}
