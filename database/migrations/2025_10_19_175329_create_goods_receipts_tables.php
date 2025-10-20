<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('goods_receipts', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->foreignId('purchase_order_id')
                ->nullable()
                ->constrained('purchase_orders')
                ->nullOnDelete();
            $t->foreignId('project_id')
                ->nullable()
                ->constrained('projects')
                ->nullOnDelete();
            $t->foreignId('supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->nullOnDelete();
            $t->date('received_date')->index();
            $t->enum('status', ['draft', 'in_progress', 'completed', 'returned'])
                ->default('draft')
                ->index();
            $t->foreignId('received_by')->constrained('users');
            $t->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $t->timestamp('verified_at')->nullable();
            $t->text('remarks')->nullable();
            $t->timestamps();
        });

        Schema::create('goods_receipt_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('goods_receipt_id')
                ->constrained('goods_receipts')
                ->cascadeOnDelete();
            $t->foreignId('purchase_order_item_id')
                ->nullable()
                ->constrained('purchase_order_items')
                ->nullOnDelete();
            $t->foreignId('material_id')->constrained('materials');
            $t->decimal('qty', 18, 2);
            $t->decimal('returned_qty', 18, 2)->default(0);
            $t->string('remarks', 255)->nullable();
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
    }
};
