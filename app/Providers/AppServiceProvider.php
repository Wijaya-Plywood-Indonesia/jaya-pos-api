<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Login PIN dibatasi per cafe + akun + alamat IP, jadi tablet cafe lain
        // (atau kasir lain di cafe yang sama) tidak ikut terhitung. Batas kedua
        // per akun tanpa melihat IP, supaya PIN 4 angka tidak bisa ditebak dari banyak IP.
        RateLimiter::for('pin-login', function (Request $request) {
            $cafe = strtolower((string) $request->input('cafe'));
            $user = (int) $request->input('user_id');

            return [
                Limit::perMinute(10)->by($cafe . '|' . $user . '|' . $request->ip()),
                Limit::perHour(60)->by($cafe . '|' . $user),
            ];
        });

        // Tebak-tebak kode aktivasi: dibatasi per IP
        RateLimiter::for('device-activate', function (Request $request) {
            return [
                Limit::perMinute(8)->by($request->ip()),
                Limit::perHour(40)->by($request->ip()),
            ];
        });
    }
}