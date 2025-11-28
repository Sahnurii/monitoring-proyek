<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_requests', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->foreignId('project_id')->constrained('projects');
            $t->foreignId('requested_by')->constrained('users');
            $t->date('request_date');
            $t->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->index();
            $t->text('notes')->nullable();
            $t->foreignId('approved_by')->nullable()->constrained('users');
            $t->timestamp('approved_at')->nullable();
            $t->decimal('total_amount', 18, 2)->default(0);
            $t->timestamps();
        });

        Schema::create('material_request_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('material_request_id')->constrained('material_requests')->cascadeOnDelete();
            $t->foreignId('material_id')->constrained('materials');
            $t->decimal('qty', 18, 2);
            $t->decimal('unit_price', 18, 2)->default(0);
            $t->decimal('total_price', 18, 2)->default(0);
            $t->string('remarks', 255)->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_request_items');
        Schema::dropIfExists('material_requests');
    }
};
