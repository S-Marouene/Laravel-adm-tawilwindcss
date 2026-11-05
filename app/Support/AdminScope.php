<?php

namespace App\Support;

use App\Models\Service;
use App\Models\User;
use App\Models\UserTerritory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Territory scoping for back-office users.
 *
 * Full-access roles (super-admin, admin, centrale) see all dossiers.
 *
 * Manager users have a set of territory assignments in `user_territories`:
 *   - row with delegation_id = null  → sees all dossiers of that governorate
 *   - row with delegation_id = X     → sees only dossiers of delegation X
 *
 * A manager can hold multiple territory rows, so the WHERE clause is an OR
 * across all assigned territories.
 */
class AdminScope
{
    /**
     * Restrict a dossier-like query to the user's assigned territories.
     * Full-access roles receive the query untouched.
     */
    public static function apply(Builder $query, ?User $user = null): Builder
    {
        $user ??= auth()->user();

        if (! $user instanceof User) {
            return $query;
        }

        if ($user->adminScope() !== 'manager') {
            return $query; // full access
        }

        $territories = $user->territories;

        if ($territories->isEmpty()) {
            // Manager with no territories sees nothing
            $query->whereRaw('1 = 0');
            return $query;
        }

        $table = $query->getModel()->getTable();

        $query->where(function (Builder $q) use ($territories, $table) {
            foreach ($territories as $territory) {
                if ($territory->delegation_id) {
                    // Specific delegation only
                    $q->orWhere(function (Builder $sub) use ($territory, $table) {
                        $sub->where("{$table}.delegation_id", $territory->delegation_id);
                    });
                } else {
                    // Whole governorate (any delegation under it)
                    $q->orWhere(function (Builder $sub) use ($territory, $table) {
                        $sub->where("{$table}.governorate_id", $territory->governorate_id);
                    });
                }
            }
        });

        return $query;
    }

    /**
     * Whether the user may access a given dossier record.
     */
    public static function canAccess(Model $record, ?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->adminScope() !== 'manager') {
            return true; // full access
        }

        $territories = $user->territories;

        foreach ($territories as $territory) {
            if ($territory->delegation_id) {
                if ((string) $record->delegation_id === (string) $territory->delegation_id) {
                    return true;
                }
            } else {
                if ((string) $record->governorate_id === (string) $territory->governorate_id) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Abort 403 when the record is outside the user's territories.
     */
    public static function authorize(Model $record, ?User $user = null): void
    {
        if (! static::canAccess($record, $user)) {
            abort(403, 'This record is outside your administrative territory.');
        }
    }

    /**
     * Abort 403 when the user is not assigned to this service.
     */
    public static function authorizeService(string $serviceKey, ?User $user = null): void
    {
        $user ??= auth()->user();

        if (! $user instanceof User || ! $user->hasService($serviceKey)) {
            abort(403, 'You are not assigned to this service.');
        }
    }

    /**
     * The admin index route of the first service the user handles.
     */
    public static function firstAllowedServiceRoute(?User $user = null): ?string
    {
        $user ??= auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        foreach (Service::orderBy('sort_order')->get() as $service) {
            if ($user->hasService($service->key)) {
                return $service->adminIndexRoute();
            }
        }

        return null;
    }
}
