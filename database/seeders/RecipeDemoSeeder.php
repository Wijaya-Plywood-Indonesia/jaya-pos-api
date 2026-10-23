<?php

namespace Database\Seeders;

use App\Models\Cafe;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Recipe;
use Illuminate\Database\Seeder;

class RecipeDemoSeeder extends Seeder
{
    public function run(): void
    {
        $cafe = Cafe::where('slug', 'jaya-cafe')->firstOrFail();

        // [satuan, harga per satuan]
        $ingredients = [
            'Biji kopi' => ['g', 280], 'Susu UHT' => ['ml', 20], 'Gula aren' => ['ml', 30],
            'Es batu' => ['g', 10], 'Gelas + tutup' => ['pcs', 900], 'Bubuk matcha' => ['g', 650],
            'Gula cair' => ['ml', 25], 'Teh bubuk' => ['g', 120], 'Susu kental manis' => ['ml', 35],
            'Nasi' => ['g', 14], 'Telur' => ['pcs', 2200], 'Bumbu' => ['porsi', 2500],
            'Ayam suwir' => ['g', 45], 'Kentang beku' => ['g', 32], 'Minyak goreng' => ['ml', 18],
            'Bumbu tabur' => ['g', 90],
        ];

        $ing = [];
        foreach ($ingredients as $name => [$unit, $cost]) {
            $ing[$name] = Ingredient::withoutGlobalScopes()->updateOrCreate(
                ['cafe_id' => $cafe->id, 'name' => $name],
                ['unit' => $unit, 'cost_per_unit' => $cost]
            );
        }

        $recipes = [
            'Es Kopi Susu Jaya' => [['Biji kopi', 18], ['Susu UHT', 120], ['Gula aren', 20], ['Es batu', 100], ['Gelas + tutup', 1]],
            'Americano' => [['Biji kopi', 18], ['Es batu', 120], ['Gelas + tutup', 1]],
            'Matcha Latte' => [['Bubuk matcha', 8], ['Susu UHT', 150], ['Gula cair', 15], ['Gelas + tutup', 1]],
            'Teh Tarik Dingin' => [['Teh bubuk', 6], ['Susu kental manis', 30], ['Es batu', 100], ['Gelas + tutup', 1]],
            'Nasi Goreng Jaya' => [['Nasi', 200], ['Telur', 1], ['Bumbu', 1], ['Ayam suwir', 50]],
            'Kentang Goreng' => [['Kentang beku', 150], ['Minyak goreng', 30], ['Bumbu tabur', 3]],
        ];

        foreach ($recipes as $menu => $rows) {
            $product = Product::withoutGlobalScopes()
                ->where('cafe_id', $cafe->id)->where('name', $menu)->first();

            if (! $product) {
                continue;
            }

            foreach ($rows as [$name, $qty]) {
                Recipe::updateOrCreate(
                    ['product_id' => $product->id, 'ingredient_id' => $ing[$name]->id],
                    ['quantity' => $qty]
                );
            }
        }
    }
}
