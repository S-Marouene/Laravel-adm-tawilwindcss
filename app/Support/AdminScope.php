<?php

namespace App\Support;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Territory scoping for back-office users.
 *
 * Dossiers carry a governorate_id + delegation_id. Administrators with a
 * 'gouvernorat' role only see dossiers of their governorate, 'delegation'
 * role only their delegation, and full-access roles see everything.
 *
 * Every service (prevention, rescue, presence, works…) stores the same two
 * FK columns, so this helper can be reused when extending the scoping to the
 * other services.
 */
class AdminScope
{
    /**
     * Restrict a query of a dossier-like model to the user's territory.
     * Percentages: full-access roles get the query untouched.
     */
    public static function apply(Builder $query, ?User $user = null): Builder
    {
        $user ??= auth()->user();

        if (! $user instanceof User) {
            return $query;
        }

        switch ($user->adminScope()) {
            case 'delegation':
                $query->where($query->getModel()->getTable() . '.delegation_id', $user->delegation_id);
                break;

            case 'governorate':
                $query->where($query->getModel()->getTable() . '.governorate_id', $user->governorate_id);
                break;
        }

        return $query;
    }

    /**
     * Whether the user may view/manage a given dossier-like record.
     */
    public static function canAccess(Model $record, ?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        switch ($user->adminScope()) {
            case 'delegation':
                return (string) $record->delegation_id === (string) $user->delegation_id;

            case 'governorate':
                return (string) $record->governorate_id === (string) $user->governorate_id;
        }

        return true;
    }

    /**
     * Guard: abort with 403 when the record is outside the user's territory.
     */
    public static function authorize(Model $record, ?User $user = null): void
    {
        if (! static::canAccess($record, $user)) {
            abort(403, 'This record is outside your administrative territory.');
        }
    }

    /**
     * Guard: abort with 403 when the user is not assigned to this service.
     * Every service controller calls it with its own service key, so a
     * centrale limited to prevention can never touch presence dossiers.
     */
    public static function authorizeService(string $serviceKey, ?User $user = null): void
    {
        $user ??= auth()->user();

        if (! $user instanceof User || ! $user->hasService($serviceKey)) {
            abort(403, 'You are not assigned to this service.');
        }
    }

    /**
     * The admin index route of the first service the user handles
     * (used by the dashboard to land scoped users on their workspace).
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
