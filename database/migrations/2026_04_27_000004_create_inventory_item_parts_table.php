<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_item_parts')) {
            return;
        }

        Schema::create('inventory_item_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->string('part_name');
            $table->string('serial_number')->nullable()->unique();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->timestamps();

            $table->index(['inventory_item_id', 'part_name']);
            $table->index(['inventory_item_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_item_parts');
    }
};
