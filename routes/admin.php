<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\BuildingActivityController;
use App\Http\Controllers\Admin\BuildingTypeController;
use App\Http\Controllers\Admin\DelegationController;
use App\Http\Controllers\Admin\DocumentTypeController;
use App\Http\Controllers\Admin\GovernorateController;
use App\Http\Controllers\Admin\NotificationCenterController;
use App\Http\Controllers\Admin\NotificationTemplateController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PreventionDossierController;
use App\Http\Controllers\Admin\SafetyFileDossierController;
use App\Http\Controllers\Admin\PreventivePresenceController;
use App\Http\Controllers\Admin\RescueCertificateController as AdminRescueCertificateController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ThemeSettingsController;
use App\Http\Controllers\Admin\TranslationController;
use App\Http\Controllers\Admin\TranslateController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\BuildingWorksDeclarationController as AdminBuildingWorksDeclarationController;
use App\Http\Controllers\Admin\SpecialInterventionController as AdminSpecialInterventionController;
use App\Http\Controllers\Admin\TrainingController as AdminTrainingController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Real-time note translation proxy (bilingual admin notes) — small,
    // cached payloads; light rate limit to protect the external engines.
    Route::post('translate', TranslateController::class)
        ->name('translate')
        ->middleware('throttle:120,1');

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
    Route::get('document-types/{documentType}/template', [DocumentTypeController::class, 'downloadTemplate'])->name('document-types.template.download');

    // Notification Center — global monitoring of every notification sent (super-admin + admin)
    Route::middleware('can:manage notifications')->group(function () {
        Route::get('notification-center', [NotificationCenterController::class, 'index'])->name('notification-center.index');
        Route::post('notification-center/mark-all-read', [NotificationCenterController::class, 'markAllRead'])->name('notification-center.mark-all-read');
        Route::delete('notification-center/clear-read', [NotificationCenterController::class, 'clearRead'])->name('notification-center.clear-read');
        Route::delete('notification-center/{notification}', [NotificationCenterController::class, 'destroy'])->name('notification-center.destroy');
    });

    // Notification Templates (super-admin only)
    Route::middleware('can:edit settings')->group(function () {
        Route::get('notification-templates', [NotificationTemplateController::class, 'index'])->name('notification-templates.index');
        Route::get('notification-templates/{notificationTemplate}/edit', [NotificationTemplateController::class, 'edit'])->name('notification-templates.edit');
        Route::put('notification-templates/{notificationTemplate}', [NotificationTemplateController::class, 'update'])->name('notification-templates.update');
        Route::post('notification-templates/reset', [NotificationTemplateController::class, 'reset'])->name('notification-templates.reset');
    });

    // Prevention Dossiers
    Route::post('prevention-dossiers/{preventionDossier}/advance', [PreventionDossierController::class, 'advanceToConsultation'])->name('prevention-dossiers.advance');
    Route::post('prevention-dossiers/{preventionDossier}/correction', [PreventionDossierController::class, 'requestCorrection'])->name('prevention-dossiers.correction');
    Route::post('prevention-dossiers/{preventionDossier}/approve', [PreventionDossierController::class, 'approve'])->name('prevention-dossiers.approve');
    Route::post('prevention-dossiers/{preventionDossier}/cancel', [PreventionDossierController::class, 'cancel'])->name('prevention-dossiers.cancel');
    // Appointments (RDV)
    Route::post('prevention-dossiers/{preventionDossier}/appointments', [PreventionDossierController::class, 'scheduleAppointment'])->name('prevention-dossiers.appointments.store');
    Route::put('prevention-dossiers/{preventionDossier}/appointments/{appointment}', [PreventionDossierController::class, 'updateAppointment'])->name('prevention-dossiers.appointments.update');
    Route::post('prevention-dossiers/{preventionDossier}/appointments/{appointment}/cancel', [PreventionDossierController::class, 'cancelAppointment'])->name('prevention-dossiers.appointments.cancel');
    // Prevention certificate (issued after a passed RDV — the applicant picks it up at a delegation)
    Route::post('prevention-dossiers/{preventionDossier}/certificate', [PreventionDossierController::class, 'issueCertificate'])->name('prevention-dossiers.certificate.store');
    Route::put('prevention-dossiers/{preventionDossier}/certificate', [PreventionDossierController::class, 'updateCertificate'])->name('prevention-dossiers.certificate.update');
    Route::delete('prevention-dossiers/{preventionDossier}/certificate', [PreventionDossierController::class, 'revokeCertificate'])->name('prevention-dossiers.certificate.destroy');
    Route::get('prevention-dossiers/documents/{document}/download', [PreventionDossierController::class, 'downloadDocument'])->name('prevention-dossiers.documents.download');
    Route::resource('prevention-dossiers', PreventionDossierController::class)->only(['index', 'show']);

    // Safety file dossiers — TWO SEPARATE admin sections (no shared routes)
    foreach (['safety-file-public' => \App\Models\Service::SERVICE_SAFETY_FILE_HABITATION, 'safety-file-hazardous' => \App\Models\Service::SERVICE_SAFETY_FILE_HAZARDOUS] as $sfPrefix => $sfKey) {
        Route::prefix($sfPrefix)->name("{$sfPrefix}.")->group(function () use ($sfPrefix) {            Route::get('/', [SafetyFileDossierController::class, 'index'])->name('index');
            Route::get('/{safetyFileDossier}', [SafetyFileDossierController::class, 'show'])->name('show');
            Route::post('/{safetyFileDossier}/advance', [SafetyFileDossierController::class, 'advanceToConsultation'])->name('advance');
            Route::post('/{safetyFileDossier}/correction', [SafetyFileDossierController::class, 'requestCorrection'])->name('correction');
            Route::post('/{safetyFileDossier}/approve', [SafetyFileDossierController::class, 'approve'])->name('approve');
            Route::post('/{safetyFileDossier}/cancel', [SafetyFileDossierController::class, 'cancel'])->name('cancel');
            Route::post('/{safetyFileDossier}/appointments', [SafetyFileDossierController::class, 'scheduleAppointment'])->name('appointments.store');
            Route::put('/{safetyFileDossier}/appointments/{appointment}', [SafetyFileDossierController::class, 'updateAppointment'])->name('appointments.update');
            Route::post('/{safetyFileDossier}/appointments/{appointment}/cancel', [SafetyFileDossierController::class, 'cancelAppointment'])->name('appointments.cancel');
            Route::post('/{safetyFileDossier}/certificate', [SafetyFileDossierController::class, 'issueCertificate'])->name('certificate.store');
            Route::put('/{safetyFileDossier}/certificate', [SafetyFileDossierController::class, 'updateCertificate'])->name('certificate.update');
            Route::delete('/{safetyFileDossier}/certificate', [SafetyFileDossierController::class, 'revokeCertificate'])->name('certificate.destroy');
            Route::get('/documents/{document}/download', [SafetyFileDossierController::class, 'downloadDocument'])->name('documents.download');
        });
    }

    // Internal intervention plan (المصادقة على مخطط التدخل الداخلي) — same pipeline as safety file
    Route::prefix('internal-intervention-plan')->name('internal-intervention-plan.')->group(function () {
        Route::get('/', [SafetyFileDossierController::class, 'index'])->name('index');
        Route::get('/{safetyFileDossier}', [SafetyFileDossierController::class, 'show'])->name('show');
        Route::post('/{safetyFileDossier}/advance', [SafetyFileDossierController::class, 'advanceToConsultation'])->name('advance');
        Route::post('/{safetyFileDossier}/correction', [SafetyFileDossierController::class, 'requestCorrection'])->name('correction');
        Route::post('/{safetyFileDossier}/approve', [SafetyFileDossierController::class, 'approve'])->name('approve');
        Route::post('/{safetyFileDossier}/cancel', [SafetyFileDossierController::class, 'cancel'])->name('cancel');
        Route::post('/{safetyFileDossier}/appointments', [SafetyFileDossierController::class, 'scheduleAppointment'])->name('appointments.store');
        Route::put('/{safetyFileDossier}/appointments/{appointment}', [SafetyFileDossierController::class, 'updateAppointment'])->name('appointments.update');
        Route::post('/{safetyFileDossier}/appointments/{appointment}/cancel', [SafetyFileDossierController::class, 'cancelAppointment'])->name('appointments.cancel');
        Route::post('/{safetyFileDossier}/certificate', [SafetyFileDossierController::class, 'issueCertificate'])->name('certificate.store');
        Route::put('/{safetyFileDossier}/certificate', [SafetyFileDossierController::class, 'updateCertificate'])->name('certificate.update');
        Route::delete('/{safetyFileDossier}/certificate', [SafetyFileDossierController::class, 'revokeCertificate'])->name('certificate.destroy');
        Route::get('/documents/{document}/download', [SafetyFileDossierController::class, 'downloadDocument'])->name('documents.download');
    });

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

    // Rescue & relief certificate (شهادة نجدة وإنقاذ) workflow actions — free procedure
    Route::post('rescue-certificates/{rescueCertificate}/advance', [AdminRescueCertificateController::class, 'advance'])->name('rescue-certificates.advance');
    Route::post('rescue-certificates/{rescueCertificate}/correction', [AdminRescueCertificateController::class, 'requestCorrection'])->name('rescue-certificates.correction');
    Route::post('rescue-certificates/{rescueCertificate}/approve', [AdminRescueCertificateController::class, 'approve'])->name('rescue-certificates.approve');
    Route::post('rescue-certificates/{rescueCertificate}/cancel', [AdminRescueCertificateController::class, 'cancel'])->name('rescue-certificates.cancel');
    Route::post('rescue-certificates/{rescueCertificate}/certificate', [AdminRescueCertificateController::class, 'issueCertificate'])->name('rescue-certificates.certificate.store');
    Route::put('rescue-certificates/{rescueCertificate}/certificate', [AdminRescueCertificateController::class, 'updateCertificate'])->name('rescue-certificates.certificate.update');
    Route::delete('rescue-certificates/{rescueCertificate}/certificate', [AdminRescueCertificateController::class, 'revokeCertificate'])->name('rescue-certificates.certificate.destroy');
    Route::get('rescue-certificates/documents/{rescueDocument}/download', [AdminRescueCertificateController::class, 'downloadDocument'])->name('rescue-certificates.documents.download');
    Route::resource('rescue-certificates', AdminRescueCertificateController::class)
        ->only(['index', 'show'])
        ->parameters(['rescue-certificates' => 'rescueCertificate']);

    // Building Works Declaration (الإعلام عن إنجاز أشغال لا تستوجب رخصة بناء) workflow actions — free procedure
    Route::post('building-works-declarations/{buildingWorksDeclaration}/advance', [AdminBuildingWorksDeclarationController::class, 'advance'])->name('building-works-declarations.advance');
    Route::post('building-works-declarations/{buildingWorksDeclaration}/correction', [AdminBuildingWorksDeclarationController::class, 'requestCorrection'])->name('building-works-declarations.correction');
    Route::post('building-works-declarations/{buildingWorksDeclaration}/approve', [AdminBuildingWorksDeclarationController::class, 'approve'])->name('building-works-declarations.approve');
    Route::post('building-works-declarations/{buildingWorksDeclaration}/cancel', [AdminBuildingWorksDeclarationController::class, 'cancel'])->name('building-works-declarations.cancel');
    Route::get('building-works-declarations/{buildingWorksDeclaration}/documents/{doc}/download', [AdminBuildingWorksDeclarationController::class, 'downloadDocument'])->name('building-works-declarations.documents.download');
    Route::resource('building-works-declarations', AdminBuildingWorksDeclarationController::class)
        ->only(['index', 'show'])
        ->parameters(['building-works-declarations' => 'buildingWorksDeclaration']);

    // Paid special interventions (التدخلات والعمليات والخدمات الخاصة بمقابل) workflow actions
    Route::post('special-interventions/{specialIntervention}/advance', [AdminSpecialInterventionController::class, 'advance'])->name('special-interventions.advance');
    Route::post('special-interventions/{specialIntervention}/correction', [AdminSpecialInterventionController::class, 'requestCorrection'])->name('special-interventions.correction');
    Route::post('special-interventions/{specialIntervention}/approve', [AdminSpecialInterventionController::class, 'approve'])->name('special-interventions.approve');
    Route::post('special-interventions/{specialIntervention}/cancel', [AdminSpecialInterventionController::class, 'cancel'])->name('special-interventions.cancel');
    Route::post('special-interventions/{specialIntervention}/serve', [AdminSpecialInterventionController::class, 'serve'])->name('special-interventions.serve');
    Route::get('special-interventions/documents/{specialInterventionDocument}/download', [AdminSpecialInterventionController::class, 'downloadDocument'])->name('special-interventions.documents.download');
    Route::resource('special-interventions', AdminSpecialInterventionController::class)
        ->only(['index', 'show'])
        ->parameters(['special-interventions' => 'specialIntervention']);

    // Training / formation (خدمات التكوين والرسكلة بمقابل) workflow actions
    Route::post('training/{trainingRequest}/advance', [AdminTrainingController::class, 'advance'])->name('training.advance');
    Route::post('training/{trainingRequest}/correction', [AdminTrainingController::class, 'requestCorrection'])->name('training.correction');
    Route::post('training/{trainingRequest}/approve', [AdminTrainingController::class, 'approve'])->name('training.approve');
    Route::post('training/{trainingRequest}/cancel', [AdminTrainingController::class, 'cancel'])->name('training.cancel');
    Route::post('training/{trainingRequest}/payment', [AdminTrainingController::class, 'recordPayment'])->name('training.payment');
    Route::post('training/{trainingRequest}/schedule', [AdminTrainingController::class, 'schedule'])->name('training.schedule');
    Route::post('training/{trainingRequest}/start', [AdminTrainingController::class, 'start'])->name('training.start');
    Route::post('training/{trainingRequest}/attendance', [AdminTrainingController::class, 'recordAttendance'])->name('training.attendance');
    Route::post('training/{trainingRequest}/result', [AdminTrainingController::class, 'recordResult'])->name('training.result');
    Route::post('training/{trainingRequest}/certificate', [AdminTrainingController::class, 'issueCertificate'])->name('training.certificate.store');
    Route::put('training/{trainingRequest}/certificate', [AdminTrainingController::class, 'updateCertificate'])->name('training.certificate.update');
    Route::delete('training/{trainingRequest}/certificate', [AdminTrainingController::class, 'revokeCertificate'])->name('training.certificate.destroy');
    Route::get('training/documents/{trainingDocument}/download', [AdminTrainingController::class, 'downloadDocument'])->name('training.documents.download');
    Route::resource('training', AdminTrainingController::class)
        ->only(['index', 'show'])
        ->parameters(['training' => 'trainingRequest']);

    // Settings
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::delete('settings/image/{key}', [SettingsController::class, 'removeImage'])->name('settings.remove-image');
    Route::post('settings/test-mail', [SettingsController::class, 'testMail'])->name('settings.test-mail');

    // Theme settings
    Route::get('theme-settings', [ThemeSettingsController::class, 'index'])->name('theme-settings.index');
    Route::put('theme-settings', [ThemeSettingsController::class, 'update'])->name('theme-settings.update');

    // Translations dictionary (FR / AR / EN in database — overrides lang/ files)
    Route::get('translations', [TranslationController::class, 'index'])->name('translations.index');
    Route::post('translations', [TranslationController::class, 'store'])->name('translations.store');
    Route::post('translations/import', [TranslationController::class, 'import'])->name('translations.import');
    Route::put('translations/{translation}', [TranslationController::class, 'update'])->name('translations.update');
    Route::delete('translations/{translation}', [TranslationController::class, 'destroy'])->name('translations.destroy');
});
