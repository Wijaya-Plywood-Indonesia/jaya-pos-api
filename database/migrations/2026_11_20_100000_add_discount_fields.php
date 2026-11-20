<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Batas potongan untuk diskon persen (contoh: 20% maksimal Rp 15.000)
        Schema::table('discounts', function (Blueprint $table) {
            $table->unsignedInteger('max_discount')->nullable()->after('min_purchase');
        });

        // Nama diskon saat transaksi terjadi, supaya riwayat tidak berubah bila promonya diedit
        Schema::table('orders', function (Blueprint $table) {
            $table->string('discount_name', 120)->nullable()->after('discount_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('discount_name');
        });

        Schema::table('discounts', function (Blueprint $table) {
            $table->dropColumn('max_discount');
        });
    }
};
