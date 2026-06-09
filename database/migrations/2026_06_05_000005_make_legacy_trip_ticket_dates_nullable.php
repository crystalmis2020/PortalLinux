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

        if (Schema::hasColumn('trip_tickets', 'date_from')) {
            DB::statement('ALTER TABLE trip_tickets MODIFY date_from DATETIME NULL');
        }

        if (Schema::hasColumn('trip_tickets', 'date_to')) {
            DB::statement('ALTER TABLE trip_tickets MODIFY date_to DATETIME NULL');
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

        if (Schema::hasColumn('trip_tickets', 'date_from')) {
            DB::statement('ALTER TABLE trip_tickets MODIFY date_from DATETIME NOT NULL');
        }

        if (Schema::hasColumn('trip_tickets', 'date_to')) {
            DB::statement('ALTER TABLE trip_tickets MODIFY date_to DATETIME NOT NULL');
        }
    }
};
