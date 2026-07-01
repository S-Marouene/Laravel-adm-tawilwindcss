<?php

use App\Http\Controllers\ProfileController;
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

    // AJAX request – return JSON so Alpine can handle the switch
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

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
