<?php

namespace App\Services;

use App\Models\Cafe;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Option;
use App\Models\OptionGroup;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Template usaha untuk cafe baru: kategori, menu, opsi, bahan, dan resep contoh.
 * Semua data contoh bertanda is_sample, jadi bisa dihapus sekaligus tanpa menyentuh data asli.
 */
class CafeTemplates
{
    public const TEMPLATES = [
        'kafe-kopi' => [
            'name' => 'Kafe kopi',
            'description' => 'Menu kopi, non-kopi, makanan, dan snack lengkap dengan opsi (ukuran, gula, es), bahan, dan resep contoh. Bisa dihapus sekali klik.',
        ],
        'kosong' => [
            'name' => 'Mulai kosong',
            'description' => 'Tanpa data contoh. Cocok kalau kamu sudah tahu mau mengisi apa.',
        ],
    ];

    public static function apply(Cafe $cafe, string $template): void
    {
        if ($template === 'kafe-kopi') {
            self::kafeKopi($cafe);
        }
    }

    private static function kafeKopi(Cafe $cafe): void
    {
        $cafeId = $cafe->id;

        // Bahan: [nama, satuan, harga per satuan, stok minimum, stok awal, bahan pemanis?]
        $ingredientRows = [
            ['Biji kopi', 'g', 200, 500, 3000, false],
            ['Susu cair', 'ml', 18, 1000, 8000, false],
            ['Gula pasir', 'g', 15, 500, 4000, true],
            ['Sirup karamel', 'ml', 90, 300, 1500, true],
            ['Bubuk matcha', 'g', 600, 100, 500, false],
            ['Teh hitam', 'g', 120, 100, 800, false],
            ['Kentang beku', 'g', 40, 1000, 6000, false],
            ['Roti tawar', 'lembar', 1000, 10, 60, false],
        ];

        $ing = [];

        foreach ($ingredientRows as [$name, $unit, $cost, $min, $stock, $sweet]) {
            $row = Ingredient::withoutGlobalScopes()->create([
                'cafe_id' => $cafeId,
                'name' => $name,
                'unit' => $unit,
                'cost_per_unit' => $cost,
                'min_stock' => $min,
                'current_stock' => $stock,
                'is_sweetener' => $sweet,
                'is_sample' => true,
            ]);

            // Stok awal dicatat di buku besar, sama seperti stok opname biasa
            StockMovement::withoutGlobalScopes()->create([
                'cafe_id' => $cafeId,
                'ingredient_id' => $row->id,
                'type' => 'opname',
                'quantity' => $stock,
                'unit_cost' => $cost,
                'reference_type' => 'opname',
                'note' => 'Stok awal (data contoh)',
            ]);

            $ing[$name] = $row->id;
        }

        // Grup opsi: [wajib dipilih, boleh pilih banyak, daftar pilihan]
        // Setiap pilihan: [nama, tambahan harga, pengaruh ke stok bahan]
        $groupDefs = [
            'Ukuran' => [true, false, [
                ['Regular', 0, []],
                ['Large', 4000, ['stock_multiplier' => 1.3]],
            ]],
            'Gula' => [true, false, [
                ['Normal', 0, []],
                ['Less Sugar', 0, ['sweet_multiplier' => 0.5]],
                ['No Sugar', 0, ['sweet_multiplier' => 0]],
            ]],
            'Es' => [true, false, [
                ['Normal', 0, []],
                ['Less Ice', 0, []],
                ['No Ice', 0, []],
            ]],
            'Topping' => [false, true, [
                ['Extra Shot', 5000, ['extra_ingredients' => [['ingredient_id' => $ing['Biji kopi'], 'quantity' => 9]]]],
                ['Whipped Cream', 4000, []],
            ]],
        ];

        $groups = [];

        foreach ($groupDefs as $name => [$required, $multiple, $options]) {
            $group = OptionGroup::withoutGlobalScopes()->create([
                'cafe_id' => $cafeId,
                'name' => $name,
                'is_required' => $required,
                'is_multiple' => $multiple,
                'is_sample' => true,
            ]);

            foreach ($options as $i => [$optionName, $price, $effects]) {
                Option::create([
                    'option_group_id' => $group->id,
                    'name' => $optionName,
                    'extra_price' => $price,
                    'sort_order' => $i,
                ] + $effects);
            }

            $groups[$name] = $group->id;
        }

        $categories = [];

        foreach (['Kopi', 'Non-Kopi', 'Makanan', 'Snack'] as $i => $name) {
            $categories[$name] = Category::withoutGlobalScopes()->create([
                'cafe_id' => $cafeId,
                'name' => $name,
                'sort_order' => $i + 1,
                'is_sample' => true,
            ])->id;
        }

        // Menu: [nama, kategori, harga, resep (bahan => takaran per porsi), grup opsi]
        $products = [
            ['Espresso', 'Kopi', 15000, ['Biji kopi' => 18], ['Ukuran', 'Gula']],
            ['Americano', 'Kopi', 18000, ['Biji kopi' => 18], ['Ukuran', 'Gula', 'Es']],
            ['Cappuccino', 'Kopi', 22000, ['Biji kopi' => 18, 'Susu cair' => 120, 'Gula pasir' => 5], ['Ukuran', 'Gula', 'Topping']],
            ['Cafe Latte', 'Kopi', 24000, ['Biji kopi' => 18, 'Susu cair' => 150, 'Gula pasir' => 8], ['Ukuran', 'Gula', 'Es', 'Topping']],
            ['Es Kopi Susu', 'Kopi', 22000, ['Biji kopi' => 18, 'Susu cair' => 100, 'Gula pasir' => 10], ['Ukuran', 'Gula', 'Es', 'Topping']],
            ['Caramel Macchiato', 'Kopi', 28000, ['Biji kopi' => 18, 'Susu cair' => 150, 'Sirup karamel' => 20], ['Ukuran', 'Gula', 'Es', 'Topping']],
            ['Matcha Latte', 'Non-Kopi', 26000, ['Bubuk matcha' => 8, 'Susu cair' => 150, 'Gula pasir' => 10], ['Ukuran', 'Gula', 'Es']],
            ['Teh Tarik Dingin', 'Non-Kopi', 16000, ['Teh hitam' => 6, 'Susu cair' => 80, 'Gula pasir' => 12], ['Ukuran', 'Gula', 'Es']],
            ['Roti Bakar Cokelat', 'Makanan', 18000, ['Roti tawar' => 2], []],
            // Tanpa resep: dianggap stok tidak terbatas
            ['Nasi Goreng', 'Makanan', 28000, [], []],
            ['Kentang Goreng', 'Snack', 17000, ['Kentang beku' => 150], []],
        ];

        foreach ($products as $n => [$name, $category, $price, $recipe, $groupNames]) {
            $product = Product::withoutGlobalScopes()->create([
                'cafe_id' => $cafeId,
                'category_id' => $categories[$category],
                'name' => $name,
                'price' => $price,
                'is_available' => true,
                'sort_order' => $n + 1,
                'is_sample' => true,
            ]);

            if ($groupNames) {
                $product->optionGroups()->attach(array_map(fn ($g) => $groups[$g], $groupNames));
            }

            self::attachSamplePhoto($product);

            foreach ($recipe as $ingredientName => $quantity) {
                Recipe::create([
                    'product_id' => $product->id,
                    'ingredient_id' => $ing[$ingredientName],
                    'quantity' => $quantity,
                ]);
            }
        }
    }

    /** Pasang foto contoh dari resources/sample-images/{nama-menu}.jpg (dilewati kalau file tidak ada). */
    private static function attachSamplePhoto(Product $product): void
    {
        $path = resource_path('sample-images/'.Str::slug($product->name).'.jpg');

        if (! is_file($path)) {
            return;
        }

        try {
            $product->addMedia($path)
                ->preservingOriginal()
                ->toMediaCollection('photo');
        } catch (\Throwable $e) {
            // Foto hanya pemanis; gagal pasang foto tidak boleh menggagalkan pendaftaran cafe
            report($e);
        }
    }

    /** Jumlah data contoh yang masih ada. */
    public static function sampleCount(int $cafeId): int
    {
        return Product::withoutGlobalScopes()->where('cafe_id', $cafeId)->where('is_sample', true)->count()
            + Category::withoutGlobalScopes()->where('cafe_id', $cafeId)->where('is_sample', true)->count()
            + OptionGroup::withoutGlobalScopes()->where('cafe_id', $cafeId)->where('is_sample', true)->count()
            + Ingredient::withoutGlobalScopes()->where('cafe_id', $cafeId)->where('is_sample', true)->whereNull('deleted_at')->count();
    }

    /**
     * Hapus semua data contoh. Kategori, grup opsi, dan bahan contoh yang sudah dipakai
     * data asli (misalnya menu buatan sendiri) dibiarkan supaya data asli tidak rusak.
     * Transaksi lama tidak terpengaruh: struk menyimpan nama dan harga sendiri.
     */
    public static function removeSamples(int $cafeId): void
    {
        DB::transaction(function () use ($cafeId) {
            $productIds = Product::withoutGlobalScopes()
                ->where('cafe_id', $cafeId)
                ->where('is_sample', true)
                ->pluck('id')
                ->all();

            if ($productIds) {
                // Hapus foto (file + baris media) lebih dulu supaya tidak jadi sampah di storage
                Media::query()
                    ->where('model_type', (new Product)->getMorphClass())
                    ->whereIn('model_id', $productIds)
                    ->get()
                    ->each
                    ->delete();

                DB::table('recipes')->whereIn('product_id', $productIds)->delete();
                DB::table('option_group_product')->whereIn('product_id', $productIds)->delete();
                DB::table('products')->whereIn('id', $productIds)->delete();
            }

            $usedCategories = Product::withoutGlobalScopes()
                ->where('cafe_id', $cafeId)
                ->whereNotNull('category_id')
                ->pluck('category_id')
                ->all();

            Category::withoutGlobalScopes()
                ->where('cafe_id', $cafeId)
                ->where('is_sample', true)
                ->whereNotIn('id', $usedCategories)
                ->delete();

            $usedGroups = DB::table('option_group_product as ogp')
                ->join('products as p', 'p.id', '=', 'ogp.product_id')
                ->where('p.cafe_id', $cafeId)
                ->pluck('ogp.option_group_id')
                ->all();

            OptionGroup::withoutGlobalScopes()
                ->where('cafe_id', $cafeId)
                ->where('is_sample', true)
                ->whereNotIn('id', $usedGroups)
                ->delete();

            $usedIngredients = DB::table('recipes as r')
                ->join('products as p', 'p.id', '=', 'r.product_id')
                ->where('p.cafe_id', $cafeId)
                ->pluck('r.ingredient_id')
                ->all();

            // Bahan dihapus lunak (soft delete), jadi riwayat stoknya tetap utuh
            Ingredient::withoutGlobalScopes()
                ->where('cafe_id', $cafeId)
                ->where('is_sample', true)
                ->whereNotIn('id', $usedIngredients)
                ->get()
                ->each
                ->delete();
        });
    }
}
