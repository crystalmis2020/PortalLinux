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
        if (!Schema::hasTable('trip_tickets')) {
            return;
        }

        Schema::table('trip_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('trip_tickets', 'vehicle_details')) {
                $table->string('vehicle_details')->nullable()->after('vehicle_id');
            }

            if (!Schema::hasColumn('trip_tickets', 'driver_name')) {
                $table->string('driver_name')->nullable()->after('driver_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('trip_tickets')) {
            return;
        }

        Schema::table('trip_tickets', function (Blueprint $table) {
            foreach (['driver_name', 'vehicle_details'] as $column) {
                if (Schema::hasColumn('trip_tickets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
