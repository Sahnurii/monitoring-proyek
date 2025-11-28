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
        Schema::create('materials', function (Blueprint $t) {
            $t->id();
            $t->string('sku')->unique();
            $t->string('name')->index();
            $t->foreignId('unit_id')->constrained('units');
            $t->decimal('min_stock', 18, 2)->default(0);
            $t->decimal('unit_price', 18, 2)->default(0);
            $t->timestamps();
            $t->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
