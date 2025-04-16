<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvestasiController;

// 🔁 Redirect root ke halaman investasi
Route::get('/', function () {
    return redirect('/investasi');
});

// ✅ Tampilkan halaman form kalkulator
Route::get('/investasi', [InvestasiController::class, 'index']);

// ✅ Proses input form
Route::post('/investasi', [InvestasiController::class, 'store'])->name('investasi.store');

// 🔁 Redirect kembali ke form dengan data sebelumnya (jika ada)
Route::get('/investasi/kembali', function () {
    return redirect('/investasi')->withInput(session('form_input'));
});
