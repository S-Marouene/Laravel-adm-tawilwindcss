{{-- Direct overrides for the centralized formation planning permissions.
     Role permissions stay untouched; only these four direct permissions are
     synced from this form (see UserController::syncPlannedPermissionOverrides). --}}
<div class="p-4 rounded-lg bg-surface-container dark:bg-gray-900/50 border border-surface-dim dark:border-gray-600 space-y-3">
    <div>
        <p class="text-sm font-medium text-on-surface dark:text-gray-200">{{ __('training.planned_perm_title') }}</p>
        <p class="text-xs text-secondary dark:text-gray-400 mt-0.5">{{ __('training.planned_perm_hint') }}</p>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
        @foreach($plannedPermissions as $permission)
            <label class="inline-flex items-start gap-2 cursor-pointer">
                <input type="checkbox" name="permissions[]" value="{{ $permission }}"
                    {{ in_array($permission, $selectedPermissions, true) ? 'checked' : '' }}
                    class="mt-0.5 rounded border-surface-dim text-purple-600 shadow-sm focus:ring-purple-500">
                <span class="text-xs text-on-surface dark:text-gray-300">
                    {{ \App\Models\TrainingPlannedSession::permissionLabels()[$permission] ?? $permission }}
                </span>
            </label>
        @endforeach
    </div>
    @error('permissions')<p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    @error('permissions.*')<p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
</div>
