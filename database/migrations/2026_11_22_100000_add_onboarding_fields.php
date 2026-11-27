<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cafes', function (Blueprint $table) {
            // Paket cafe. Semua masih gratis, kolomnya disiapkan supaya nanti tidak perlu ubah struktur data.
            $table->string('plan', 20)->default('free')->after('slug');
            // Cara cafe terdaftar: admin (dibuat super admin) atau self (daftar mandiri)
            $table->string('registered_via', 20)->default('admin')->after('plan');
        });

        // Data contoh dari template usaha, supaya bisa dihapus sekaligus tanpa menyentuh data asli
        foreach (['categories', 'products', 'option_groups', 'ingredients'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->boolean('is_sample')->default(false);
            });
        }
    }

    public function down(): void
    {
        foreach (['categories', 'products', 'option_groups', 'ingredients'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropColumn('is_sample');
            });
        }

        Schema::table('cafes', function (Blueprint $table) {
            $table->dropColumn(['plan', 'registered_via']);
        });
    }
};
