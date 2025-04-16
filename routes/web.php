<?php

use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\CatatanController;

// Route::get('/', function () {
//     return view('welcome');
// });


// Route::get('/', [CatatanController::class, 'index']);
// Route::post('/catatan', [CatatanController::class, 'store'])->name('catatan.store');

use App\Http\Controllers\InvestasiController;

Route::get('/investasi', [InvestasiController::class, 'index']);
Route::post('/investasi', [InvestasiController::class, 'store'])->name('investasi.store');
Route::get('/investasi/kembali', function () {
    return redirect('/investasi')->withInput(session('form_input'));
});

