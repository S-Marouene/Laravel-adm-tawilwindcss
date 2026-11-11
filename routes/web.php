<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MesDemandesController;
use App\Http\Controllers\PreventionRequestController;
use App\Models\PreventionDossier;
use App\Models\PreventivePresenceRequest;
use App\Models\RescueCertificate;
use App\Models\BuildingWorksDeclaration;
use App\Http\Controllers\PreventivePresenceController;
use App\Http\Controllers\RescueCertificateController;
use App\Http\Controllers\BuildingWorksDeclarationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\App;

// Locale Switcher
Route::get('locale/{locale}', function (string $locale) {
    if (! in_array($locale, ['fr', 'ar'])) {
        $locale = 'fr';
    }

    Session::put('locale', $locale);
    App::setLocale($locale);

    // Also set a cookie for instant read by middleware
    cookie()->queue(cookie()->forever('user_locale', $locale));

    // AJAX request - return JSON so Alpine can handle the switch
    if (request()->expectsJson() || request()->ajax()) {
        return response()->json([
            'locale' => $locale,
            'dir' => $locale === 'ar' ? 'rtl' : 'ltr',
        ]);
    }

    return redirect()->back();
})->name('locale.switch');

// Landing page. Logged-in users also see this page; the layout decides
// what to show based on whether they already have submitted requests.
Route::get('/', function () {
    $hasRequests = auth()->check() && (
        PreventionDossier::where('user_id', auth()->id())->whereNotIn('status', ['draft'])->exists()
        || PreventivePresenceRequest::where('user_id', auth()->id())->whereNotIn('status', ['draft'])->exists()
        || RescueCertificate::where('user_id', auth()->id())->whereNotIn('status', ['draft'])->exists()
        || BuildingWorksDeclaration::where('user_id', auth()->id())->whereNotIn('status', ['draft'])->exists()
    );

    return view('welcome', ['hasRequests' => $hasRequests]);
});

// Unified list of all the user's submitted demandes
Route::get('/mes-demandes', [MesDemandesController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('mes-demandes');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    // Dossier list
    Route::get('/prevention-requests', [PreventionRequestController::class, 'index'])->name('prevention-requests.index');

    // Multi-step create
    Route::get('/prevention-requests/create', [PreventionRequestController::class, 'create'])->name('prevention-requests.create');
    Route::post('/prevention-requests/step1', [PreventionRequestController::class, 'storeStep1'])->name('prevention-requests.step1.store');
    Route::get('/prevention-requests/{dossier}/step1/edit', [PreventionRequestController::class, 'editStep1'])->name('prevention-requests.step1.edit');
    Route::put('/prevention-requests/{dossier}/step1', [PreventionRequestController::class, 'updateStep1'])->name('prevention-requests.step1.update');
    Route::get('/prevention-requests/{dossier}/step2', [PreventionRequestController::class, 'showStep2'])->name('prevention-requests.step2.show');
    Route::post('/prevention-requests/{dossier}/step2', [PreventionRequestController::class, 'storeStep2'])->name('prevention-requests.step2.store');
    Route::get('/prevention-requests/{dossier}/step3', [PreventionRequestController::class, 'showStep3'])->name('prevention-requests.step3.show');
    Route::post('/prevention-requests/{dossier}/step3', [PreventionRequestController::class, 'storeStep3'])->name('prevention-requests.step3.store');

    // Legacy store (kept for backward-compat, now redirects to step1)
    Route::post('/prevention-requests', [PreventionRequestController::class, 'store'])->name('prevention-requests.store');

    // Targeted correction form (replaces the old 3-step resubmit flow)
    Route::get('/prevention-requests/{dossier}/correct', [PreventionRequestController::class, 'showCorrectForm'])->name('prevention-requests.correct');
    Route::post('/prevention-requests/{dossier}/correct', [PreventionRequestController::class, 'submitCorrection'])->name('prevention-requests.correct.submit');

    // Legacy resubmit POST — kept so old bookmarks/emails don't break; redirects to the new correct page
    Route::post('/prevention-requests/{dossier}/resubmit', [PreventionRequestController::class, 'resubmitRedirect'])->name('prevention-requests.resubmit');

    // Certificate delivery choice (after the RDV passed) — pickup / post / digital(soon)
    Route::post('/prevention-requests/{dossier}/delivery', [PreventionRequestController::class, 'chooseDelivery'])
        ->name('prevention-requests.delivery');

    // Structures JSON endpoint (kept for edit form)
    Route::get('/prevention-requests/structures', [PreventionRequestController::class, 'structures'])->name('prevention-requests.structures');

    // Official form/template attached to a document type (download → fill → rescan → upload)
    Route::get('/prevention-requests/document-template/{documentType}', [PreventionRequestController::class, 'downloadTemplate'])
        ->name('prevention-requests.document-template.download');

    // Edit / Update / Delete
    Route::get('/prevention-requests/{id}/edit', [PreventionRequestController::class, 'edit'])->name('prevention-requests.edit');
    Route::put('/prevention-requests/{id}', [PreventionRequestController::class, 'update'])->name('prevention-requests.update');
    Route::delete('/prevention-requests/{id}', [PreventionRequestController::class, 'destroy'])->name('prevention-requests.destroy');

    // Dossier details (placed last so {dossier} doesn't capture /create, /structures etc.)
    Route::get('/prevention-requests/{dossier}', [PreventionRequestController::class, 'show'])->name('prevention-requests.show');
    Route::get('/prevention-requests/{dossier}/documents/{document}/download', [PreventionRequestController::class, 'downloadDocument'])
        ->name('prevention-requests.documents.download');
    Route::delete('/prevention-requests/{dossier}/documents/{document}', [PreventionRequestController::class, 'deleteDocument'])
        ->name('prevention-requests.documents.delete');
    Route::get('/preventive-presence', [PreventivePresenceController::class, 'index'])->name('preventive-presence.index');

    // Multi-step create
    Route::get('/preventive-presence/create', [PreventivePresenceController::class, 'create'])->name('preventive-presence.create');
    Route::post('/preventive-presence/step1', [PreventivePresenceController::class, 'storeStep1'])->name('preventive-presence.step1.store');
    Route::get('/preventive-presence/{presenceRequest}/step1/edit', [PreventivePresenceController::class, 'editStep1'])->name('preventive-presence.step1.edit');
    Route::put('/preventive-presence/{presenceRequest}/step1', [PreventivePresenceController::class, 'updateStep1'])->name('preventive-presence.step1.update');
    Route::get('/preventive-presence/{presenceRequest}/step2', [PreventivePresenceController::class, 'showStep2'])->name('preventive-presence.step2.show');
    Route::post('/preventive-presence/{presenceRequest}/step2', [PreventivePresenceController::class, 'storeStep2'])->name('preventive-presence.step2.store');

    // Resubmit after correction
    Route::post('/preventive-presence/{presenceRequest}/resubmit', [PreventivePresenceController::class, 'resubmit'])->name('preventive-presence.resubmit');

    // Delete a local draft
    Route::delete('/preventive-presence/{presenceRequest}', [PreventivePresenceController::class, 'destroy'])->name('preventive-presence.destroy');

    // ── Rescue & relief certificate (شهادة نجدة وإنقاذ) ──
    Route::get('/rescue-certificates', [RescueCertificateController::class, 'index'])->name('rescue-certificates.index');
    Route::get('/rescue-certificates/create', [RescueCertificateController::class, 'create'])->name('rescue-certificates.create');
    Route::get('/rescue-certificates/{rescueCertificate}', [RescueCertificateController::class, 'show'])->name('rescue-certificates.show');

    // Official form/template attached to a document type (download → fill → rescan → upload)
    Route::get('/rescue-certificates/document-template/{documentType}', [RescueCertificateController::class, 'downloadTemplate'])
        ->name('rescue-certificates.document-template.download');

    // Targeted correction form (fields + documents flagged by the admin)
    Route::get('/rescue-certificates/{rescueCertificate}/correct', [RescueCertificateController::class, 'showCorrectForm'])->name('rescue-certificates.correct');
    Route::post('/rescue-certificates/{rescueCertificate}/correct', [RescueCertificateController::class, 'submitCorrection'])->name('rescue-certificates.correct.submit');

    Route::post('/rescue-certificates/step1', [RescueCertificateController::class, 'storeStep1'])->name('rescue-certificates.step1.store');
    Route::get('/rescue-certificates/{rescueCertificate}/step1/edit', [RescueCertificateController::class, 'editStep1'])->name('rescue-certificates.step1.edit');
    Route::put('/rescue-certificates/{rescueCertificate}/step1', [RescueCertificateController::class, 'updateStep1'])->name('rescue-certificates.step1.update');
    Route::get('/rescue-certificates/{rescueCertificate}/step2', [RescueCertificateController::class, 'showStep2'])->name('rescue-certificates.step2.show');
    Route::post('/rescue-certificates/{rescueCertificate}/step2', [RescueCertificateController::class, 'storeStep2'])->name('rescue-certificates.step2.store');
    Route::post('/rescue-certificates/{rescueCertificate}/resubmit', [RescueCertificateController::class, 'resubmit'])->name('rescue-certificates.resubmit');
    Route::post('/rescue-certificates/{rescueCertificate}/delivery', [RescueCertificateController::class, 'chooseDelivery'])->name('rescue-certificates.delivery');
    Route::get('/rescue-certificates/documents/{rescueDocument}/download', [RescueCertificateController::class, 'downloadDocument'])->name('rescue-certificates.documents.download');
    Route::delete('/rescue-certificates/{rescueCertificate}', [RescueCertificateController::class, 'destroy'])->name('rescue-certificates.destroy');

    // ── Building Works Declaration (الإعلام عن إنجاز أشغال لا تستوجب رخصة بناء) ──
    Route::get('/building-works-declarations', [BuildingWorksDeclarationController::class, 'index'])->name('building-works-declarations.index');
    Route::get('/building-works-declarations/create', [BuildingWorksDeclarationController::class, 'create'])->name('building-works-declarations.create');
    Route::get('/building-works-declarations/{buildingWorksDeclaration}', [BuildingWorksDeclarationController::class, 'show'])->name('building-works-declarations.show');

    // Official form/template attached to a document type (download → fill → rescan → upload)
    Route::get('/building-works-declarations/document-template/{documentType}', [BuildingWorksDeclarationController::class, 'downloadTemplate'])
        ->name('building-works-declarations.document-template.download');

    // Targeted correction form (fields + documents flagged by the admin)
    Route::get('/building-works-declarations/{buildingWorksDeclaration}/correct', [BuildingWorksDeclarationController::class, 'showCorrectForm'])->name('building-works-declarations.correct');
    Route::post('/building-works-declarations/{buildingWorksDeclaration}/correct', [BuildingWorksDeclarationController::class, 'submitCorrection'])->name('building-works-declarations.correct.submit');

    Route::post('/building-works-declarations/step1', [BuildingWorksDeclarationController::class, 'storeStep1'])->name('building-works-declarations.step1.store');
    Route::get('/building-works-declarations/{buildingWorksDeclaration}/step1/edit', [BuildingWorksDeclarationController::class, 'editStep1'])->name('building-works-declarations.step1.edit');
    Route::put('/building-works-declarations/{buildingWorksDeclaration}/step1', [BuildingWorksDeclarationController::class, 'updateStep1'])->name('building-works-declarations.step1.update');
    Route::get('/building-works-declarations/{buildingWorksDeclaration}/step2', [BuildingWorksDeclarationController::class, 'showStep2'])->name('building-works-declarations.step2.show');
    Route::post('/building-works-declarations/{buildingWorksDeclaration}/step2', [BuildingWorksDeclarationController::class, 'storeStep2'])->name('building-works-declarations.step2.store');
    Route::post('/building-works-declarations/{buildingWorksDeclaration}/resubmit', [BuildingWorksDeclarationController::class, 'resubmit'])->name('building-works-declarations.resubmit');
    Route::get('/building-works-declarations/{buildingWorksDeclaration}/documents/{buildingWorksDocument}/download', [BuildingWorksDeclarationController::class, 'downloadDocument'])->name('building-works-declarations.documents.download');
    Route::delete('/building-works-declarations/{buildingWorksDeclaration}', [BuildingWorksDeclarationController::class, 'destroy'])->name('building-works-declarations.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ── Notification API ──────────────────────────────────────────────────────
// All authenticated users (citizen + admin) can access their own notifications.
Route::middleware('auth')->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/all',                                   [\App\Http\Controllers\NotificationController::class, 'viewAll'])->name('index');
    Route::get('/',                                      [\App\Http\Controllers\NotificationController::class, 'index'])->name('api');
    Route::patch('/{notification}/read',                [\App\Http\Controllers\NotificationController::class, 'markRead'])->name('read');
    Route::post('/mark-all-read',                       [\App\Http\Controllers\NotificationController::class, 'markAllRead'])->name('read-all');
    Route::delete('/{notification}',                    [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('destroy');
    Route::delete('/',                                  [\App\Http\Controllers\NotificationController::class, 'destroyAll'])->name('destroy-all');
});

require __DIR__.'/auth.php';
