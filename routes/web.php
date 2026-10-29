<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MesDemandesController;
use App\Http\Controllers\PreventionRequestController;
use App\Models\PreventionDossier;
use App\Models\PreventivePresenceRequest;
use App\Models\RescueCertificate;
use App\Models\WorksNotification;
use App\Http\Controllers\PreventivePresenceController;
use App\Http\Controllers\RescueCertificateController;
use App\Http\Controllers\WorksNotificationController;
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
        || WorksNotification::where('user_id', auth()->id())->whereNotIn('status', ['draft'])->exists()
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

    // Resubmit after correction
    Route::post('/prevention-requests/{dossier}/resubmit', [PreventionRequestController::class, 'resubmit'])->name('prevention-requests.resubmit');

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
    Route::post('/rescue-certificates/step1', [RescueCertificateController::class, 'storeStep1'])->name('rescue-certificates.step1.store');
    Route::get('/rescue-certificates/{rescueCertificate}/step1/edit', [RescueCertificateController::class, 'editStep1'])->name('rescue-certificates.step1.edit');
    Route::put('/rescue-certificates/{rescueCertificate}/step1', [RescueCertificateController::class, 'updateStep1'])->name('rescue-certificates.step1.update');
    Route::get('/rescue-certificates/{rescueCertificate}/step2', [RescueCertificateController::class, 'showStep2'])->name('rescue-certificates.step2.show');
    Route::post('/rescue-certificates/{rescueCertificate}/step2', [RescueCertificateController::class, 'storeStep2'])->name('rescue-certificates.step2.store');
    Route::post('/rescue-certificates/{rescueCertificate}/resubmit', [RescueCertificateController::class, 'resubmit'])->name('rescue-certificates.resubmit');
    Route::get('/rescue-certificates/documents/{rescueDocument}/download', [RescueCertificateController::class, 'downloadDocument'])->name('rescue-certificates.documents.download');
    Route::delete('/rescue-certificates/{rescueCertificate}', [RescueCertificateController::class, 'destroy'])->name('rescue-certificates.destroy');

    // ── Works notification (الإعلام عن إنجاز أشغال لا تستوجب رخصة بناء) ──
    Route::get('/works-notifications', [WorksNotificationController::class, 'index'])->name('works-notifications.index');
    Route::get('/works-notifications/create', [WorksNotificationController::class, 'create'])->name('works-notifications.create');
    Route::post('/works-notifications/step1', [WorksNotificationController::class, 'storeStep1'])->name('works-notifications.step1.store');
    Route::get('/works-notifications/{worksNotification}/step1/edit', [WorksNotificationController::class, 'editStep1'])->name('works-notifications.step1.edit');
    Route::put('/works-notifications/{worksNotification}/step1', [WorksNotificationController::class, 'updateStep1'])->name('works-notifications.step1.update');
    Route::get('/works-notifications/{worksNotification}/step2', [WorksNotificationController::class, 'showStep2'])->name('works-notifications.step2.show');
    Route::post('/works-notifications/{worksNotification}/step2', [WorksNotificationController::class, 'storeStep2'])->name('works-notifications.step2.store');
    Route::post('/works-notifications/{worksNotification}/resubmit', [WorksNotificationController::class, 'resubmit'])->name('works-notifications.resubmit');
    Route::delete('/works-notifications/{worksNotification}', [WorksNotificationController::class, 'destroy'])->name('works-notifications.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
