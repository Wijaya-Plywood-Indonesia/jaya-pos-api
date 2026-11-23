<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ReadsDeviceData;
use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockController extends Controller
{
    use ReadsDeviceData;

    // Beli stok dari tablet. Hanya owner atau manajer, karena ini mencatat biaya.
    public function restock(Request $request, StockService $stock): JsonResponse
    {
        $cafeId = $this->cafeIdOrFail($request);
        $user = $request->user();

        if (! $user->hasAnyRole(['owner', 'manager'])) {
            return response()->json(['message' => 'Hanya owner atau manajer yang boleh membeli stok.'], 403);
        }

        $data = $request->validate([
            'ingredient_id' => ['required', 'integer'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:100000000'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        $ingredient = Ingredient::withoutGlobalScopes()
            ->where('cafe_id', $cafeId)
            ->whereNull('deleted_at')
            ->find($data['ingredient_id']);

        if (! $ingredient) {
            return response()->json(['message' => 'Bahan tidak ditemukan.'], 404);
        }

        $purchase = $stock->restock(
            $ingredient,
            (float) $data['quantity'],
            $user->id,
            $user->outlet_id,
            $data['note'] ?? null,
        );

        return response()->json([
            'status' => 'ok',
            'cost' => $purchase->total,
            'ingredient' => [
                'id' => $ingredient->id,
                'name' => $ingredient->name,
                'unit' => $ingredient->unit,
                'stock' => (float) $ingredient->fresh()->current_stock,
            ],
        ], 201);
    }
}
