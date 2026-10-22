<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\OptionGroup;
use App\Models\Product;
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

        $products = Product::with('optionGroups:id')
            ->where('cafe_id', $cafeId)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'category_id' => $p->category_id,
                'price' => $p->price,
                'is_available' => $p->is_available,
                'option_group_ids' => $p->optionGroups->pluck('id')->values(),
            ]);

        $tables = DiningTable::where('cafe_id', $cafeId)
            ->orderBy('name')
            ->get(['id', 'name', 'capacity']);

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'categories' => $categories,
            'option_groups' => $groups,
            'products' => $products,
            'tables' => $tables,
        ]);
    }
}