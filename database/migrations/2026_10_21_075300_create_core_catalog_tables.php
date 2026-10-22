<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu baris = satu cafe pelanggan (tenant)
        Schema::create('cafes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('theme_color', 9)->default('#d7263d');
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('service_percent', 5, 2)->default(0);
            $table->unsignedSmallInteger('rounding')->default(0); // 0, 100, 500
            $table->string('receipt_footer')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('outlets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->timestamps();
        });

        // cafe_id kosong = super admin (Anda sendiri)
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('cafe_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('outlet_id')->nullable()->after('cafe_id')->constrained()->nullOnDelete();
            $table->string('pin')->nullable()->after('password'); // disimpan ter-hash
            $table->boolean('is_active')->default(true)->after('pin');
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Harga disimpan sebagai integer Rupiah (tanpa desimal)
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedInteger('price');
            $table->boolean('is_available')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['cafe_id', 'is_available']);
        });

        // Contoh grup: Gula, Es, Ukuran, Topping
        Schema::create('option_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_multiple')->default(false); // true = boleh pilih banyak
            $table->boolean('is_required')->default(false);
            $table->timestamps();
        });

        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('extra_price')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Menu mana saja yang memakai grup opsi tertentu
        Schema::create('option_group_product', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('option_group_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_id', 'option_group_id']);
        });

        Schema::create('dining_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outlet_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dining_tables');
        Schema::dropIfExists('option_group_product');
        Schema::dropIfExists('options');
        Schema::dropIfExists('option_groups');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('outlet_id');
            $table->dropConstrainedForeignId('cafe_id');
            $table->dropColumn(['pin', 'is_active']);
        });

        Schema::dropIfExists('outlets');
        Schema::dropIfExists('cafes');
    }
};