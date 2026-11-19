<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MenuController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ShiftController;

Route::prefix('v1')->group(function () {
    // Publik: daftar staf untuk layar login, dan login PIN.
    // Batas login PIN didefinisikan di AppServiceProvider (limiter 'pin-login'):
    // per cafe + akun + IP, dan per akun per jam.
    Route::get('cafes/{slug}/staff', [AuthController::class, 'staff']);
    Route::post('auth/pin-login', [AuthController::class, 'pinLogin'])->middleware('throttle:pin-login');

    // Butuh token
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::get('/auth/offline-staff', [AuthController::class, 'offlineStaff']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('menu', [MenuController::class, 'index']);
        Route::post('shifts', [ShiftController::class, 'upsert'])->middleware('throttle:300,1');
        Route::post('orders', [OrderController::class, 'store'])->middleware('throttle:300,1');
    });
});