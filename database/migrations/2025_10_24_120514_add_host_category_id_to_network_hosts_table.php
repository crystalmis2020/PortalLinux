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
        if (!Schema::hasTable('network_hosts') || Schema::hasColumn('network_hosts', 'host_category_id')) {
            return;
        }

        Schema::table('network_hosts', function (Blueprint $table) {
            $table->foreignId('host_category_id')
                  ->nullable()
                  ->after('description')
                  ->constrained('host_categories')
                  ->nullOnDelete()
                  ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('network_hosts') || !Schema::hasColumn('network_hosts', 'host_category_id')) {
            return;
        }

        Schema::table('network_hosts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('host_category_id');
        });
    }
};
