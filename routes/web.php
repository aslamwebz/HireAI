<?php

use App\Http\Controllers\CvAnalysisController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('ai-settings', 'ai-settings')->name('ai-settings');
    Route::view('cvs', 'cvs')->name('cvs.index');
    Route::view('cv-analyzer', 'cv-analyzer')->name('cv-analyzer');
    Route::get('cv-analysis/{cvAnalysis}', [CvAnalysisController::class, 'show'])->name('cv-analysis.show');
    Route::delete('cv-analysis/{cvAnalysis}', [CvAnalysisController::class, 'destroy'])->name('cv-analysis.destroy');
    Route::view('cover-letter', 'cover-letter')->name('cover-letter');
    Route::view('jobs', 'jobs')->name('jobs.index');
    Route::view('jobs/{jobApplication}', 'jobs.show')->name('jobs.show');
    Route::view('interview-prep', 'interview-prep')->name('interview-prep');
    Route::view('pricing', 'pricing')->name('pricing');

    Route::post('subscription/checkout', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::get('subscription/success', [SubscriptionController::class, 'success'])->name('subscription.success');
    Route::get('subscription/billing', [SubscriptionController::class, 'billing'])->name('subscription.billing');

    Route::prefix('resumes')->name('resumes.')->group(function () {
        Route::get('/', [ResumeController::class, 'index'])->name('index');
        Route::get('create', [ResumeController::class, 'create'])->name('create');
        Route::post('/', [ResumeController::class, 'store'])->name('store');
        Route::post('parse', [ResumeController::class, 'parseUpload'])->name('parse');
        Route::get('{resume}', [ResumeController::class, 'show'])->name('show');
        Route::get('{resume}/edit', [ResumeController::class, 'edit'])->name('edit');
        Route::put('{resume}', [ResumeController::class, 'update'])->name('update');
        Route::delete('{resume}', [ResumeController::class, 'destroy'])->name('destroy');
        Route::get('{resume}/download', [ResumeController::class, 'download'])->name('download');
        Route::get('{resume}/preview', [ResumeController::class, 'preview'])->name('preview');
        Route::post('{resume}/duplicate', [ResumeController::class, 'duplicate'])->name('duplicate');
        Route::post('{resume}/primary', [ResumeController::class, 'setPrimary'])->name('primary');
        Route::post('{resume}/versions/{version}/restore', [ResumeController::class, 'restoreVersion'])->name('versions.restore');
        Route::post('{resume}/optimize', [ResumeController::class, 'optimize'])->name('optimize');
        Route::post('{resume}/optimize-ats', [ResumeController::class, 'optimizeForAts'])->name('optimize-ats');
    });

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ResumeController::class, 'getProfile'])->name('show');
        Route::put('/', [ResumeController::class, 'updateProfile'])->name('update');
        Route::post('import', [ResumeController::class, 'importProfileFromUpload'])->name('import');
    });
});

// Cashier's webhook route is automatically handled if you use the default stripe/webhook path,
// but we'll keeping it explicit if you want to use a specific URL or CSRF protection.
Route::post('stripe/webhook', [\Laravel\Cashier\Http\Controllers\WebhookController::class, 'handleWebhook'])->name('cashier.webhook');

require __DIR__.'/settings.php';
