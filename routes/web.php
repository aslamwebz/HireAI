<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('ai-settings', 'ai-settings')->name('ai-settings');
    Route::view('cv-analyzer', 'cv-analyzer')->name('cv-analyzer');
    Route::view('cover-letter', 'cover-letter')->name('cover-letter');
    Route::view('jobs', 'jobs')->name('jobs.index');
    Route::view('interview-prep', 'interview-prep')->name('interview-prep');
    Route::view('pricing', 'pricing')->name('pricing');
});

require __DIR__.'/settings.php';
