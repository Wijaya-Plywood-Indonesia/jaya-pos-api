<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Discount;
use App\Models\DiningTable;
use App\Models\Ingredient;
use App\Models\OptionGroup;
use App\Models\Product;
use App\Models\Recipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    // Satu paket data katalog, supaya aplikasi bisa menyimpannya untuk mode offline
    public function index(Request $request): JsonResponse
    {
        $cafeId = $request->user()->cafe_id;

        $categories = Category::where('cafe_id', $cafeId)
            ->orderBy('sort_order')
            ->get(['id', 'name']);

        $groups = OptionGroup::with('options')
            ->where('cafe_id', $cafeId)
            ->get()
            ->map(fn ($g) => [
                'id' => $g->id,
                'name' => $g->name,
                'is_multiple' => $g->is_multiple,
                'is_required' => $g->is_required,
                'options' => $g->options->map(fn ($o) => [
                    'id' => $o->id,
                    'name' => $o->name,
                    'extra_price' => $o->extra_price,
                ])->values(),
            ]);

        // Owner dan manajer boleh melihat resep dan HPP. Kasir hanya melihat sisa porsi.
        $canSeeCost = $request->user()->hasAnyRole(['owner', 'manager']);

        $productRows = Product::with(['optionGroups:id', 'media'])
            ->where('cafe_id', $cafeId)
            ->orderBy('sort_order')
            ->get();

        $recipes = Recipe::whereIn('product_id', $productRows->pluck('id'))
            ->with('ingredient')
            ->get()
            ->groupBy('product_id');

        $products = $productRows
            ->map(function ($p) use ($recipes, $canSeeCost) {
                $url = $p->getFirstMediaUrl('photo', 'thumb');

                $lines = $recipes->get($p->id, collect())
                    ->filter(fn ($r) => $r->ingredient && ! $r->ingredient->trashed() && $r->quantity > 0);

                // Sisa porsi dari bahan yang paling cepat habis. null = menu tanpa resep (tanpa batas).
                $portions = $lines->isEmpty()
                    ? null
                    : (int) max(0, floor($lines->map(fn ($r) => $r->ingredient->current_stock / $r->quantity)->min() + 1e-9));

                $extra = $canSeeCost ? [
                    'hpp' => (int) round($lines->sum(fn ($r) => $r->quantity * $r->ingredient->cost_per_unit)),
                    'recipe' => $lines->map(fn ($r) => [
                        'ingredient_id' => $r->ingredient_id,
                        'quantity' => $r->quantity,
                    ])->values(),
                ] : [];

                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'category_id' => $p->category_id,
                    'price' => $p->price,
                    'is_available' => $p->is_available,
                    // Path relatif, contoh /storage/12/thumb.jpg. Aplikasi menambahkan alamat servernya sendiri.
                    'image_path' => $url ? parse_url($url, PHP_URL_PATH) : null,
                    'option_group_ids' => $p->optionGroups->pluck('id')->values(),
                    'portions' => $portions,
                ] + $extra;
            })
            ->values();

        $ingredients = Ingredient::where('cafe_id', $cafeId)
            ->orderBy('name')
            ->get()
            ->map(fn ($i) => [
                'id' => $i->id,
                'name' => $i->name,
                'unit' => $i->unit,
                'stock' => $i->current_stock,
                'min_stock' => $i->min_stock,
                'is_sweetener' => $i->is_sweetener,
            ] + ($canSeeCost ? ['cost_per_unit' => $i->cost_per_unit] : []))
            ->values();

        $tables = DiningTable::where('cafe_id', $cafeId)
            ->get(['id', 'name', 'capacity'])
            // Urutan natural: Meja 2 sebelum Meja 10
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        // Promo dan voucher yang aktif. Kode voucher ikut dikirim supaya tablet bisa
        // memeriksanya saat offline, tapi tidak pernah ditampilkan di layar kasir.
        $discounts = Discount::where('cafe_id', $cafeId)
            ->activeNow()
            ->orderBy('name')
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'code' => $d->code,
                'type' => $d->type,
                'value' => $d->value,
                'min_purchase' => $d->min_purchase,
                'max_discount' => $d->max_discount,
                'starts_at' => $d->starts_at?->toIso8601String(),
                'ends_at' => $d->ends_at?->toIso8601String(),
            ])
            ->values();

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'categories' => $categories,
            'option_groups' => $groups,
            'products' => $products,
            'tables' => $tables,
            'discounts' => $discounts,
            'ingredients' => $ingredients,
        ]);
    }
}