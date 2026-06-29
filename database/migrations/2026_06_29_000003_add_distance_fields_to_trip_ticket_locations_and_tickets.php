<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_ticket_locations', function (Blueprint $table) {
            if (! Schema::hasColumn('trip_ticket_locations', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('psgc_10_digit_code');
            }

            if (! Schema::hasColumn('trip_ticket_locations', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }

            if (! Schema::hasColumn('trip_ticket_locations', 'distance_from_maramag_km')) {
                $table->decimal('distance_from_maramag_km', 8, 2)->nullable()->after('longitude');
            }
        });

        Schema::table('trip_tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('trip_tickets', 'distance_km')) {
                $table->decimal('distance_km', 8, 2)->nullable()->after('trip_ticket_location_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trip_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('trip_tickets', 'distance_km')) {
                $table->dropColumn('distance_km');
            }
        });

        Schema::table('trip_ticket_locations', function (Blueprint $table) {
            foreach (['distance_from_maramag_km', 'longitude', 'latitude'] as $column) {
                if (Schema::hasColumn('trip_ticket_locations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
