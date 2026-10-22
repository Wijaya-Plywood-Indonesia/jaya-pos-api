<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---------- Promo ----------
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 30)->nullable();
            $table->enum('type', ['percent', 'amount']);
            $table->unsignedInteger('value');
            $table->unsignedInteger('min_purchase')->default(0);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['cafe_id', 'code']);
        });

        // ---------- Shift kasir ----------
        // ID berupa UUID yang dibuat di perangkat, supaya aman saat offline
        Schema::create('shifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('cafe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outlet_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->unsignedInteger('opening_cash')->default(0);
            $table->unsignedInteger('expected_cash')->nullable(); // dihitung sistem
            $table->unsignedInteger('closing_cash')->nullable();  // dihitung kasir
            $table->integer('cash_difference')->nullable();       // boleh negatif
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['cafe_id', 'opened_at']);
        });

        // ---------- Order ----------
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary(); // dibuat di perangkat kasir
            $table->foreignId('cafe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outlet_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->foreignId('cashier_id')->constrained('users');
            $table->foreignId('dining_table_id')->nullable()->constrained('dining_tables')->nullOnDelete();
            $table->foreignId('discount_id')->nullable()->constrained('discounts')->nullOnDelete();

            $table->string('order_number', 40); // dibuat perangkat, contoh: A1-1021-0001
            $table->enum('type', ['dine_in', 'take_away', 'delivery'])->default('dine_in');
            $table->enum('status', ['open', 'paid', 'void', 'refunded'])->default('open');
            $table->enum('kitchen_status', ['new', 'preparing', 'ready', 'served'])->default('new');
            $table->string('customer_name')->nullable();
            $table->string('note')->nullable();

            // Semua angka dalam Rupiah (integer)
            $table->unsignedInteger('subtotal')->default(0);
            $table->unsignedInteger('discount_total')->default(0);
            $table->unsignedInteger('service_amount')->default(0);
            $table->unsignedInteger('tax_amount')->default(0);
            $table->integer('rounding')->default(0);
            $table->unsignedInteger('total')->default(0);

            $table->dateTime('ordered_at');          // waktu di perangkat
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); // PIN owner
            $table->string('void_reason')->nullable();
            $table->dateTime('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['cafe_id', 'order_number']);
            $table->index(['cafe_id', 'status', 'ordered_at']);
        });

        // Nama dan harga disalin saat transaksi, jadi laporan lama tidak berubah
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->unsignedInteger('unit_price');
            $table->unsignedSmallInteger('quantity');
            $table->unsignedInteger('options_total')->default(0);
            $table->unsignedInteger('line_total');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('order_item_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('option_id')->nullable()->constrained()->nullOnDelete();
            $table->string('option_name');
            $table->unsignedInteger('extra_price')->default(0);
        });

        // Satu order boleh punya beberapa pembayaran (split bayar)
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->enum('method', ['cash', 'qris', 'card', 'transfer']);
            $table->unsignedInteger('amount');
            $table->unsignedInteger('cash_received')->nullable();
            $table->unsignedInteger('change_amount')->default(0);
            $table->string('reference')->nullable();
            $table->dateTime('paid_at');
            $table->timestamps();
        });

        // ---------- Bahan baku & resep ----------
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('unit', 15); // g, ml, pcs, porsi
            $table->decimal('cost_per_unit', 12, 2)->default(0);
            $table->decimal('min_stock', 14, 3)->default(0);
            $table->decimal('current_stock', 14, 3)->default(0); // cache dari stock_movements
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->unique(['product_id', 'ingredient_id']);
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 30)->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outlet_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->dateTime('purchased_at');
            $table->unsignedInteger('total')->default(0);
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained();
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_cost', 12, 2);
            $table->unsignedInteger('subtotal');
        });

        // Buku besar stok: setiap perubahan stok dicatat di sini
        // quantity positif = stok masuk, negatif = stok keluar
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outlet_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['purchase', 'sale', 'void_return', 'waste', 'opname', 'adjustment']);
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->string('reference_type', 40)->nullable(); // order, purchase, dll
            $table->string('reference_id', 40)->nullable();
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['ingredient_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('recipes');
        Schema::dropIfExists('ingredients');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_item_options');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('discounts');
    }
};