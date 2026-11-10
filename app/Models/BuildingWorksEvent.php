<?php

namespace App\Models;

use App\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuildingBuildingBuildingBuildingWorksEvent extends Model
{
    use HasTranslations;

    protected $fillable = [
        'works_notification_id', 'user_id', 'type', 'note', 'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    /**
     * Bilingual note attached to this event — stored as {"fr": …, "ar": …},
     * reading returns the localized string.
     */
    protected function note(): Attribute
    {
        return $this->translated('note');
    }

    // Possible types — useful for reference:
    // submitted | resubmitted | advanced_to_consultation | correction_requested
    // approved | cancelled

    // -----------------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------------

    public function notification(): BelongsTo
    {
        return $this->belongsTo(BuildingWorksDeclaration::class, 'works_notification_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // -----------------------------------------------------------------------
    // Display helpers
    // -----------------------------------------------------------------------

    /** Human-readable label for the timeline */
    public function typeLabel(): string
    {
        return match ($this->type) {
            'submitted'                 => app()->getLocale() === 'ar'
                ? 'إيداع الإعلام من قِبل صاحب الطلب'
                : 'Notification soumise par le demandeur',
            'resubmitted'               => app()->getLocale() === 'ar'
                ? 'إعادة إيداع الإعلام بعد التصحيح'
                : 'Notification re-soumise après correction',
            'advanced_to_consultation'  => app()->getLocale() === 'ar'
                ? 'الإعلام بُوشر بدراسته (قيد الدراسة)'
                : 'Notification avancée en consultation',
            'correction_requested'      => app()->getLocale() === 'ar'
                ? 'طُلب تصحيح من الإدارة'
                : 'Correction demandée par l\'administration',
            'approved'                  => app()->getLocale() === 'ar'
                ? 'تمت الموافقة — أُبلغت الحماية المدنية بالأشغال'
                : 'Notification approuvée — Protection Civile informée des travaux',
            'cancelled'                 => app()->getLocale() === 'ar'
                ? 'تم إلغاء الإعلام'
                : 'Notification annulée',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    /** Tailwind dot class used by the timeline */
    public function typeDot(): string
    {
        return match ($this->type) {
            'submitted', 'resubmitted'  => 'bg-teal-500',
            'advanced_to_consultation'  => 'bg-purple-500',
            'correction_requested'      => 'bg-amber-500',
            'approved'                  => 'bg-emerald-500',
            'cancelled'                 => 'bg-red-400',
            default                     => 'bg-gray-400',
        };
    }
}
