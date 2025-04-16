<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvestasiController;

Route::get('/', function () {
    return redirect('/investasi');
});

Route::get('/investasi', [InvestasiController::class, 'index']); // ← aktifkan ini!
Route::post('/investasi', [InvestasiController::class, 'store'])->name('investasi.store');
Route::get('/investasi/kembali', function () {
    return redirect('/investasi')->withInput(session('form_input'));
});
