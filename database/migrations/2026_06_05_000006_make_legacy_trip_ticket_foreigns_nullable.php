<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('trip_tickets')) {
            return;
        }

        foreach (['department_id', 'vehicle_id', 'driver_id'] as $column) {
            if (Schema::hasColumn('trip_tickets', $column)) {
                DB::statement("ALTER TABLE trip_tickets MODIFY {$column} BIGINT UNSIGNED NULL");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('trip_tickets')) {
            return;
        }

        foreach (['department_id', 'vehicle_id', 'driver_id'] as $column) {
            if (Schema::hasColumn('trip_tickets', $column)) {
                DB::statement("ALTER TABLE trip_tickets MODIFY {$column} BIGINT UNSIGNED NOT NULL");
            }
        }
    }
};
