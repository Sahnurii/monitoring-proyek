<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('material_id')
                ->constrained('materials')
                ->restrictOnDelete();

            $table->foreignId('project_id')
                ->nullable()
                ->constrained('projects')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->enum('movement_type', ['in', 'out', 'transfer_in', 'transfer_out', 'adjustment'])
                ->default('adjustment')
                ->index();

            $table->nullableMorphs('reference');

            $table->decimal('quantity', 18, 2);
            $table->decimal('stock_before', 18, 2)->default(0);
            $table->decimal('stock_after', 18, 2)->default(0);

            $table->timestamp('occurred_at')->index();
            $table->string('remarks', 255)->nullable();

            $table->timestamps();

            $table->index(['material_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
