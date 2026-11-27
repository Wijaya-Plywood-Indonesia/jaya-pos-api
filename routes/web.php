<?php

use App\Http\Controllers\RegisterCafeController;
use Illuminate\Support\Facades\Route;

// Halaman depan
Route::view('/', 'landing');

// Pendaftaran mandiri. Dibatasi 10 percobaan per jam per alamat IP.
Route::get('/daftar', [RegisterCafeController::class, 'show'])->name('daftar');
Route::post('/daftar', [RegisterCafeController::class, 'store'])->middleware('throttle:10,60');

// Syarat dan kebijakan privasi (draf, tinjau dulu sebelum dipublikasikan)
Route::view('/syarat', 'legal', ['type' => 'syarat'])->name('syarat');
Route::view('/privasi', 'legal', ['type' => 'privasi'])->name('privasi');
