<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivationCode;
use App\Models\Cafe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeviceController extends Controller
{
    // Tukar kode aktivasi dengan kode cafe. Belum login: ini langkah pertama tablet.
    public function activate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20'],
        ]);

        $raw = ActivationCode::normalize($data['code']);
        $invalid = response()->json(['message' => 'Kode tidak valid atau sudah kedaluwarsa.'], 422);

        if (strlen($raw) !== 8) {
            return $invalid;
        }

        $hash = ActivationCode::hash($raw);

        // Satu UPDATE atomik: dua tablet yang memakai kode yang sama bersamaan, hanya satu yang berhasil
        $claimed = DB::table('activation_codes')
            ->where('code_hash', $hash)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['used_at' => now(), 'updated_at' => now()]);

        if (! $claimed) {
            return $invalid;
        }

        $cafeId = DB::table('activation_codes')->where('code_hash', $hash)->value('cafe_id');
        $cafe = Cafe::where('id', $cafeId)->where('is_active', true)->first();

        if (! $cafe) {
            return $invalid;
        }

        return response()->json([
            'cafe_slug' => $cafe->slug,
            'cafe_name' => $cafe->name,
        ]);
    }
}
