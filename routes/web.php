<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PreventionRequestController;
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

Route::get('/', function () {
    return view('welcome');
});

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

    // Edit / Update / Delete
    Route::get('/prevention-requests/{id}/edit', [PreventionRequestController::class, 'edit'])->name('prevention-requests.edit');
    Route::put('/prevention-requests/{id}', [PreventionRequestController::class, 'update'])->name('prevention-requests.update');
    Route::delete('/prevention-requests/{id}', [PreventionRequestController::class, 'destroy'])->name('prevention-requests.destroy');

    // Dossier details (placed last so {dossier} doesn't capture /create, /structures etc.)
    Route::get('/prevention-requests/{dossier}', [PreventionRequestController::class, 'show'])->name('prevention-requests.show');
    Route::get('/prevention-requests/{dossier}/documents/{document}/download', [PreventionRequestController::class, 'downloadDocument'])
        ->name('prevention-requests.documents.download');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
