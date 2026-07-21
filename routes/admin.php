<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ThemeSettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::resource('users', UserController::class)->except(['show']);
    Route::resource('roles', RoleController::class)->except(['show']);
    Route::resource('permissions', PermissionController::class)->except(['show']);

    // Activity logs
    Route::delete('activity-logs/clear', [ActivityLogController::class, 'clear'])->name('activity-logs.clear');
    Route::resource('activity-logs', ActivityLogController::class)->only(['index', 'show', 'destroy']);

    // Advanced UI Routes
    Route::prefix('advanced-ui')->name('advanced-ui.')->group(function () {
        Route::get('stepper', function () {
            return view('admin.advanced-ui.stepper');
        })->name('stepper');
        Route::get('modals', function () {
            return view('admin.advanced-ui.modals');
        })->name('modals');
        Route::get('accordions', function () {
            return view('admin.advanced-ui.accordions');
        })->name('accordions');
        Route::get('tabs', function () {
            return view('admin.advanced-ui.tabs');
        })->name('tabs');
        Route::get('carousel', function () {
            return view('admin.advanced-ui.carousel');
        })->name('carousel');
        Route::get('tooltips', function () {
            return view('admin.advanced-ui.tooltips');
        })->name('tooltips');
        Route::get('dropdowns', function () {
            return view('admin.advanced-ui.dropdowns');
        })->name('dropdowns');
        Route::get('sweet-alerts', function () {
            return view('admin.advanced-ui.sweet-alerts');
        })->name('sweet-alerts');
    });

    // Settings
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::delete('settings/image/{key}', [SettingsController::class, 'removeImage'])->name('settings.remove-image');
    Route::post('settings/test-mail', [SettingsController::class, 'testMail'])->name('settings.test-mail');

    // Theme settings
    Route::get('theme-settings', [ThemeSettingsController::class, 'index'])->name('theme-settings.index');
    Route::put('theme-settings', [ThemeSettingsController::class, 'update'])->name('theme-settings.update');
});
