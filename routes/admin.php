<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\GovernorateController;
use App\Http\Controllers\Admin\DelegationController;
use App\Http\Controllers\Admin\BuildingTypeController;
use App\Http\Controllers\Admin\BuildingActivityController;
use App\Http\Controllers\Admin\DocumentTypeController;
use App\Http\Controllers\Admin\PreventionDossierController;
use App\Http\Controllers\Admin\PreventivePresenceController;
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

    // Reference Data Management
    Route::resource('governorates', GovernorateController::class)->except(['show']);
    Route::resource('delegations', DelegationController::class)->except(['show']);
    Route::resource('building-types', BuildingTypeController::class)->except(['show']);
    Route::resource('building-activities', BuildingActivityController::class)->except(['show']);
    Route::resource('document-types', DocumentTypeController::class)->except(['show']);

    // Prevention Dossiers
    Route::post('prevention-dossiers/{preventionDossier}/advance',    [PreventionDossierController::class, 'advanceToConsultation'])->name('prevention-dossiers.advance');
    Route::post('prevention-dossiers/{preventionDossier}/correction', [PreventionDossierController::class, 'requestCorrection'])->name('prevention-dossiers.correction');
    Route::post('prevention-dossiers/{preventionDossier}/approve',    [PreventionDossierController::class, 'approve'])->name('prevention-dossiers.approve');
    Route::post('prevention-dossiers/{preventionDossier}/cancel',     [PreventionDossierController::class, 'cancel'])->name('prevention-dossiers.cancel');
    // Appointments (RDV)
    Route::post('prevention-dossiers/{preventionDossier}/appointments',                              [PreventionDossierController::class, 'scheduleAppointment'])->name('prevention-dossiers.appointments.store');
    Route::put('prevention-dossiers/{preventionDossier}/appointments/{appointment}',                 [PreventionDossierController::class, 'updateAppointment'])->name('prevention-dossiers.appointments.update');
    Route::delete('prevention-dossiers/{preventionDossier}/appointments/{appointment}',              [PreventionDossierController::class, 'deleteAppointment'])->name('prevention-dossiers.appointments.destroy');
    // Prevention certificate (issued after a passed RDV — the applicant picks it up at a delegation)
    Route::post('prevention-dossiers/{preventionDossier}/certificate',   [PreventionDossierController::class, 'issueCertificate'])->name('prevention-dossiers.certificate.store');
    Route::put('prevention-dossiers/{preventionDossier}/certificate',    [PreventionDossierController::class, 'updateCertificate'])->name('prevention-dossiers.certificate.update');
    Route::delete('prevention-dossiers/{preventionDossier}/certificate', [PreventionDossierController::class, 'revokeCertificate'])->name('prevention-dossiers.certificate.destroy');
    Route::resource('prevention-dossiers', PreventionDossierController::class)->only(['index', 'show']);

    // Preventive Presence (الحضور الوقائي) workflow actions
    Route::post('preventive-presence/{preventivePresence}/advance', [PreventivePresenceController::class, 'advance'])->name('preventive-presence.advance');
    Route::post('preventive-presence/{preventivePresence}/correction', [PreventivePresenceController::class, 'requestCorrection'])->name('preventive-presence.correction');
    Route::post('preventive-presence/{preventivePresence}/approve', [PreventivePresenceController::class, 'approve'])->name('preventive-presence.approve');
    Route::post('preventive-presence/{preventivePresence}/payment', [PreventivePresenceController::class, 'recordPayment'])->name('preventive-presence.payment');
    Route::post('preventive-presence/{preventivePresence}/serve', [PreventivePresenceController::class, 'markServed'])->name('preventive-presence.serve');
    Route::post('preventive-presence/{preventivePresence}/cancel', [PreventivePresenceController::class, 'cancel'])->name('preventive-presence.cancel');
    Route::resource('preventive-presence', PreventivePresenceController::class)
        ->only(['index', 'show'])
        ->parameters(['preventive-presence' => 'preventivePresence']);

    // Settings
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::delete('settings/image/{key}', [SettingsController::class, 'removeImage'])->name('settings.remove-image');
    Route::post('settings/test-mail', [SettingsController::class, 'testMail'])->name('settings.test-mail');

    // Theme settings
    Route::get('theme-settings', [ThemeSettingsController::class, 'index'])->name('theme-settings.index');
    Route::put('theme-settings', [ThemeSettingsController::class, 'update'])->name('theme-settings.update');
});
