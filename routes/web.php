<?php

use App\Http\Controllers\CvAnalysisController;
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
    Route::view('interview-prep', 'interview-prep')->name('interview-prep');
    Route::view('pricing', 'pricing')->name('pricing');

    Route::post('subscription/checkout', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::get('subscription/success', [SubscriptionController::class, 'success'])->name('subscription.success');
    Route::get('subscription/billing', [SubscriptionController::class, 'billing'])->name('subscription.billing');
});

Route::post('stripe/webhook', [SubscriptionController::class, 'webhook'])->name('stripe.webhook');

require __DIR__.'/settings.php';
