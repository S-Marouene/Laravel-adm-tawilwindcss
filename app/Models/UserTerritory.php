<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents one territory assignment for a manager user.
 *
 * - delegation_id = null  → covers the full governorate (all its delegations)
 * - delegation_id = X     → covers only delegation X within the governorate
 *
 * A user can have many rows (multiple territories).
 */
class UserTerritory extends Model
{
    protected $fillable = ['user_id', 'governorate_id', 'delegation_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function delegation(): BelongsTo
    {
        return $this->belongsTo(Delegation::class);
    }

    /**
     * Human-readable label, e.g. "Tunis" or "Tunis → Bab El Bhar".
     */
    public function label(): string
    {
        $gov = $this->governorate?->name ?? "Gov #{$this->governorate_id}";

        if ($this->delegation_id) {
            $del = $this->delegation?->name ?? "Del #{$this->delegation_id}";
            return "{$gov} → {$del}";
        }

        return $gov . ' (all delegations)';
    }
}
