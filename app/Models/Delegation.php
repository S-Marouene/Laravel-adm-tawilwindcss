<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delegation extends Model
{
    protected $fillable = ['governorate_id', 'name_ar', 'name_fr', 'name_en', 'office_address', 'office_phone', 'office_hours'];

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

    /**
     * Pickup-office details maintained by the administration, used on the
     * applicant's certificate card when the delivery method is "pickup".
     */
    public function officeDetails(): ?array
    {
        if (! $this->office_address && ! $this->office_phone && ! $this->office_hours) {
            return null;
        }

        return [
            'address'   => $this->office_address,
            'phone'     => $this->office_phone,
            'hours'     => $this->office_hours,
        ];
    }
}
