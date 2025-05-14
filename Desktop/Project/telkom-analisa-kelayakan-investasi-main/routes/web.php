<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvestasiController;

// Redirect root (/) langsung ke form investasi
Route::get('/', [InvestasiController::class, 'form'])->name('home');

Route::get('/investasi', [InvestasiController::class, 'form'])->name('investasi.form');
Route::post('/investasi', [InvestasiController::class, 'store'])->name('investasi.store');


Route::get('/investasi/kembali', function () {
    return redirect()->route('investasi.form')->withInput();
})->name('investasi.kembali');

Route::get('/investasi/download-template', [InvestasiController::class, 'downloadTemplate'])->name('investasi.download');

Route::post('/investasi/download-template', [InvestasiController::class, 'downloadTemplate'])->name('investasi.download');

// Route::post('/investasi/upload-screenshot', [InvestasiController::class, 'uploadScreenshot'])->name('upload.screenshot');
