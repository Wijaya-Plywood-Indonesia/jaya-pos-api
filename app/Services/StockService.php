<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\Option;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\Recipe;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

/**
 * Semua perubahan stok bahan lewat sini, dan selalu dicatat di buku besar (stock_movements).
 * Kolom ingredients.current_stock hanya salinan hasil jumlah buku besar.
 */
class StockService
{
    /**
     * Pemakaian bahan sebuah pesanan: [ingredient_id => jumlah].
     *
     * Resep menu x jumlah porsi, disesuaikan pilihan menu:
     * - stock_multiplier mengalikan semua bahan resep (Large)
     * - sweet_multiplier mengalikan bahan pemanis saja (Less Sugar, No Sugar)
     * - extra_ingredients menambah bahan lain (extra shot, boba)
     */
    public function consumption(Order $order): array
    {
        $order->loadMissing('items.options');

        $productIds = $order->items->pluck('product_id')->filter()->unique()->all();
        $recipes = Recipe::whereIn('product_id', $productIds)->with('ingredient')->get()->groupBy('product_id');

        $optionIds = $order->items
            ->flatMap(fn ($item) => $item->options->pluck('option_id'))
            ->filter()
            ->unique()
            ->all();
        $options = Option::whereIn('id', $optionIds)->get()->keyBy('id');

        $use = [];

        foreach ($order->items as $item) {
            // Menunya sudah dihapus: tidak ada resep yang bisa dipakai
            if (! $item->product_id) {
                continue;
            }

            $selected = $item->options
                ->map(fn ($o) => $o->option_id ? $options->get($o->option_id) : null)
                ->filter();

            $allMult = $selected->reduce(fn ($carry, $o) => $carry * (float) $o->stock_multiplier, 1.0);
            $sweetMult = $selected->reduce(fn ($carry, $o) => $carry * (float) $o->sweet_multiplier, 1.0);

            foreach ($recipes->get($item->product_id, collect()) as $line) {
                $ingredient = $line->ingredient;

                if (! $ingredient || $ingredient->trashed()) {
                    continue;
                }

                $qty = $line->quantity
                    * $allMult
                    * ($ingredient->is_sweetener ? $sweetMult : 1.0)
                    * $item->quantity;

                $use[$line->ingredient_id] = ($use[$line->ingredient_id] ?? 0) + $qty;
            }

            foreach ($selected as $option) {
                foreach ($option->extra_ingredients ?? [] as $extra) {
                    $id = (int) ($extra['ingredient_id'] ?? 0);
                    $qty = (float) ($extra['quantity'] ?? 0);

                    if ($id > 0 && $qty > 0) {
                        $use[$id] = ($use[$id] ?? 0) + $qty * $item->quantity;
                    }
                }
            }
        }

        return array_filter($use, fn ($q) => $q > 0);
    }

    /**
     * Kurangi stok sesuai pesanan. Dipanggil sekali saat pesanan pertama kali diterima server.
     * Stok boleh menjadi negatif: transaksi sudah terjadi dan dibayar, jadi tidak boleh ditolak.
     */
    public function deductForOrder(Order $order): void
    {
        $use = $this->consumption($order);

        if (! $use) {
            return;
        }

        $ingredients = Ingredient::withoutGlobalScopes()
            ->where('cafe_id', $order->cafe_id)
            ->whereIn('id', array_keys($use))
            ->get()
            ->keyBy('id');

        foreach ($use as $ingredientId => $qty) {
            $ingredient = $ingredients->get($ingredientId);

            if (! $ingredient) {
                continue;
            }

            $qty = round($qty, 3);

            StockMovement::withoutGlobalScopes()->create([
                'cafe_id' => $order->cafe_id,
                'outlet_id' => $order->outlet_id,
                'ingredient_id' => $ingredient->id,
                'type' => 'sale',
                'quantity' => -$qty,
                'unit_cost' => $ingredient->cost_per_unit,
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'created_by' => $order->cashier_id,
            ]);

            $ingredient->decrement('current_stock', $qty);
        }
    }

    /** Beli stok: menambah stok, mencatat pembelian (biaya), dan satu baris buku besar. */
    public function restock(
        Ingredient $ingredient,
        float $quantity,
        int $userId,
        ?int $outletId = null,
        ?string $note = null,
    ): Purchase {
        return DB::transaction(function () use ($ingredient, $quantity, $userId, $outletId, $note) {
            $unitCost = (float) $ingredient->cost_per_unit;
            $total = (int) round($quantity * $unitCost);

            $purchase = Purchase::withoutGlobalScopes()->create([
                'cafe_id' => $ingredient->cafe_id,
                'outlet_id' => $outletId,
                'user_id' => $userId,
                'purchased_at' => now(),
                'total' => $total,
                'note' => $note,
            ]);

            $purchase->items()->create([
                'ingredient_id' => $ingredient->id,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'subtotal' => $total,
            ]);

            StockMovement::withoutGlobalScopes()->create([
                'cafe_id' => $ingredient->cafe_id,
                'outlet_id' => $outletId,
                'ingredient_id' => $ingredient->id,
                'type' => 'purchase',
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'reference_type' => 'purchase',
                'reference_id' => (string) $purchase->id,
                'note' => $note,
                'created_by' => $userId,
            ]);

            $ingredient->increment('current_stock', $quantity);

            return $purchase;
        });
    }

    /** Koreksi stok (stok opname): samakan stok sistem dengan hasil hitung di rak. */
    public function adjustTo(Ingredient $ingredient, float $actual, int $userId, ?string $note = null): void
    {
        DB::transaction(function () use ($ingredient, $actual, $userId, $note) {
            $delta = round($actual - (float) $ingredient->current_stock, 3);

            if ($delta == 0.0) {
                return;
            }

            StockMovement::withoutGlobalScopes()->create([
                'cafe_id' => $ingredient->cafe_id,
                'ingredient_id' => $ingredient->id,
                'type' => 'opname',
                'quantity' => $delta,
                'unit_cost' => $ingredient->cost_per_unit,
                'reference_type' => 'opname',
                'note' => $note,
                'created_by' => $userId,
            ]);

            $ingredient->update(['current_stock' => $actual]);
        });
    }
}
