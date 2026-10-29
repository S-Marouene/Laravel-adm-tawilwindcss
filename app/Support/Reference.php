<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Citizen-facing reference generator.
 *
 * Format: {PREFIX}{last two digits of the year}-{4-digit sequence}, e.g. PRV26-0001.
 * Sequences are per service and per year — `0001` restarts for 2027 (PRV27-0001).
 * The reference is assigned once, on submission, and stored in the `reference`
 * column of each request table.
 */
class Reference
{
    /** Service → prefix used in reference codes. */
    public const PREFIXES = [
        'prevention' => 'PRV', // Dossier de prévention
        'presence'   => 'PRS', // Présence préventive
        'rescue'     => 'SEC', // Certificat de secours et sauvetage
        'works'      => 'TRV', // Notification de travaux
    ];

    public static function prefix(string $service): string
    {
        return self::PREFIXES[$service] ?? 'ONPC';
    }

    /** Last two digits of a given (or current) year: 2026 → "26". */
    public static function yearDigit(?int $year = null): string
    {
        return substr((string) ($year ?? now()->year), -2);
    }

    /**
     * "PRV26-0001" for a prevention dossier submitted in 2026, etc.
     * $for gives the table the sequence lives in (each service counts its own).
     */
    public static function make(string $service, Model $for, ?int $year = null): string
    {
        $head = self::prefix($service) . self::yearDigit($year);
        $count = (int) $for->newQuery()
            ->where('reference', 'like', $head . '-%')
            ->count();

        do {
            $reference = $head . '-' . str_pad((string) (++$count), 4, '0', STR_PAD_LEFT);
        } while ($for->newQuery()->where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * Placeholder shown on drafts whose reference is not assigned yet.
     */
    public static function provisional(string $service): string
    {
        return self::prefix($service) . self::yearDigit() . '-####';
    }
}
