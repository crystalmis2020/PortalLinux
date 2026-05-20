<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('inventory_items', 'stock_quantity')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->unsignedInteger('stock_quantity')->default(0)->after('item_type');
            });
        }

        if (Schema::hasTable('inventory_item_releases')) {
            return;
        }

        Schema::create('inventory_item_releases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('released_to')->nullable();
            $table->string('department')->nullable();
            $table->string('location')->nullable();
            $table->string('purpose')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at');
            $table->timestamps();

            $table->index(['inventory_item_id', 'released_at']);
            $table->index('released_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_item_releases');

        if (Schema::hasColumn('inventory_items', 'stock_quantity')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->dropColumn('stock_quantity');
            });
        }
    }
};
