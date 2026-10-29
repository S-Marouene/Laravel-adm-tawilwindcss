<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    // Which service's checklist the document belongs to
    public const SERVICE_PREVENTION = 'prevention';
    public const SERVICE_PREVENTIVE_PRESENCE = 'preventive-presence';
    public const SERVICE_RESCUE_CERTIFICATE = 'rescue-certificate';
    public const SERVICE_WORKS_NOTIFICATION = 'works-notification';

    protected $fillable = [
        'name_ar', 'name_fr', 'name_en',
        'service_key',
        'services',
        'is_conditional',
        'sort_order',
        'template_path',
        'template_name',
    ];

    protected $casts = [
        'is_conditional' => 'boolean',
        'sort_order'     => 'integer',
        'services'       => 'array',
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

    /** Building types this document applies to (empty pivot = all building types). */
    public function buildingTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            BuildingType::class,
            'building_type_document_type',
            'document_type_id',
            'building_type_id'
        );
    }

    /**
     * Does this document type apply to the given building type?
     * STRICT : le document n'apparaît chez l'utilisateur que si le type de
     * bâtiment du dossier a été explicitement coché côté administration.
     */
    public function appliesToBuildingType(?int $buildingTypeId = null): bool
    {
        if ($this->buildingTypes->isEmpty()) {
            return false; // aucune restriction configurée → jamais proposé
        }

        return $buildingTypeId !== null && $this->buildingTypes->contains('id', $buildingTypeId);
    }

    /** File extension of the attached official form (uppercase), e.g. "PDF". */
    public function templateExtension(): string
    {
        return strtoupper(pathinfo($this->template_name ?: (string) $this->template_path, PATHINFO_EXTENSION));
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

    /** Scope types belonging to a given service checklist (multi-services aware). */
    public function scopeService($query, string $serviceKey)
    {
        return $query->where(function ($q) use ($serviceKey) {
            $q->whereJsonContains('services', $serviceKey)
              ->orWhereNull('services');
        });
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
            $query->service($serviceKey);
        }

        return $query->pluck($col, 'id')
            ->toArray();
    }

    /** Normalized list of services this document belongs to. */
    public function serviceKeys(): array
    {
        $keys = $this->services ?? [];

        if (empty($keys) && ! empty($this->service_key)) {
            $keys = [$this->service_key];
        }

        return array_values(array_unique(array_filter($keys)));
    }

    public function hasService(string $serviceKey): bool
    {
        return in_array($serviceKey, $this->serviceKeys(), true);
    }
}
