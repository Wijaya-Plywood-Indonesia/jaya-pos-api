<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bahan pemanis (gula, sirup) ikut berkurang atau hilang saat memilih "Less Sugar" / "No Sugar"
        Schema::table('ingredients', function (Blueprint $table) {
            $table->boolean('is_sweetener')->default(false)->after('unit');
        });

        // Pengaruh sebuah pilihan menu ke pemakaian bahan:
        // stock_multiplier  = pengali semua bahan resep (Large = 1.30)
        // sweet_multiplier  = pengali bahan pemanis saja (Less Sugar = 0.50, No Sugar = 0)
        // extra_ingredients = bahan tambahan yang ikut terpakai, [{ingredient_id, quantity}]
        Schema::table('options', function (Blueprint $table) {
            $table->decimal('stock_multiplier', 5, 2)->default(1)->after('extra_price');
            $table->decimal('sweet_multiplier', 5, 2)->default(1)->after('stock_multiplier');
            $table->json('extra_ingredients')->nullable()->after('sweet_multiplier');
        });
    }

    public function down(): void
    {
        Schema::table('options', function (Blueprint $table) {
            $table->dropColumn(['stock_multiplier', 'sweet_multiplier', 'extra_ingredients']);
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn('is_sweetener');
        });
    }
};