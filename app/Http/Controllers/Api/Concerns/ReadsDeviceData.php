<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

trait ReadsDeviceData
{
    /** ID cafe milik pengguna; ditolak bila akun tidak terhubung ke cafe aktif. */
    protected function cafeIdOrFail(Request $request): int
    {
        $user = $request->user()->loadMissing('cafe');

        abort_unless($user->cafe_id && $user->cafe?->is_active, 403, 'Akun ini tidak terhubung ke cafe aktif.');

        return (int) $user->cafe_id;
    }

    /** Waktu dari perangkat (ISO 8601) diubah ke zona waktu aplikasi sebelum disimpan. */
    protected function deviceTime(?string $value): ?Carbon
    {
        return $value ? Carbon::parse($value)->setTimezone(config('app.timezone')) : null;
    }

    /** ID bila milik cafe yang sama, selain itu null (kosong, atau milik cafe lain). */
    protected function ownedId(string $model, ?int $id, int $cafeId): ?int
    {
        if (! $id) {
            return null;
        }

        return $model::withoutGlobalScopes()
            ->where('id', $id)
            ->where('cafe_id', $cafeId)
            ->exists() ? $id : null;
    }
}