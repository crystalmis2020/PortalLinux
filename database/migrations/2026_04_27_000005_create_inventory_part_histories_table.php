<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_part_histories')) {
            return;
        }

        Schema::create('inventory_part_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('old_part_id')->nullable()->constrained('inventory_item_parts')->nullOnDelete();
            $table->foreignId('new_part_id')->nullable()->constrained('inventory_item_parts')->nullOnDelete();
            $table->string('part_name');
            $table->string('action_type');
            $table->string('reason')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('action_date');
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['inventory_item_id', 'part_name']);
            $table->index(['inventory_item_id', 'action_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_part_histories');
    }
};
