<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ReadsDeviceData;
use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    use ReadsDeviceData;

    // Shift dibuat di perangkat kasir. Endpoint ini idempoten: dikirim saat dibuka,
    // lalu dikirim lagi saat ditutup, dan selalu memperbarui shift yang sama.
    public function upsert(Request $request): JsonResponse
    {
        $cafeId = $this->cafeIdOrFail($request);

        $data = $request->validate([
            'id' => ['required', 'uuid'],
            'user_id' => ['nullable', 'integer'],
            'outlet_id' => ['nullable', 'integer'],
            'opened_at' => ['required', 'date'],
            'opening_cash' => ['required', 'integer', 'min:0'],
            'closed_at' => ['nullable', 'date'],
            'closing_cash' => ['nullable', 'integer', 'min:0'],
            'expected_cash' => ['nullable', 'integer', 'min:0'],
        ]);

        $shift = Shift::withoutGlobalScopes()->find($data['id']);

        if ($shift && (int) $shift->cafe_id !== $cafeId) {
            return response()->json(['message' => 'ID shift sudah dipakai.'], 409);
        }

        // Shift yang sudah ditutup di server tidak dibuka atau diubah lagi
        if ($shift && $shift->closed_at !== null) {
            return response()->json(['status' => 'unchanged', 'id' => $shift->id]);
        }

        $closedAt = $this->deviceTime($data['closed_at'] ?? null);
        $closing = $data['closing_cash'] ?? null;
        $expected = $data['expected_cash'] ?? null;

        $record = $shift ?? new Shift;
        $record->forceFill([
            'id' => $data['id'],
            'cafe_id' => $cafeId,
            'outlet_id' => $this->ownedId(Outlet::class, $data['outlet_id'] ?? null, $cafeId),
            'user_id' => $this->ownedId(User::class, $data['user_id'] ?? null, $cafeId) ?? $request->user()->id,
            'opened_at' => $this->deviceTime($data['opened_at']),
            'opening_cash' => $data['opening_cash'],
            'closed_at' => $closedAt,
            'closing_cash' => $closedAt ? $closing : null,
            'expected_cash' => $closedAt ? $expected : null,
            'cash_difference' => ($closedAt && $closing !== null && $expected !== null)
                ? $closing - $expected
                : null,
        ])->save();

        return response()->json(
            ['status' => $shift ? 'updated' : 'created', 'id' => $record->id],
            $shift ? 200 : 201
        );
    }
}