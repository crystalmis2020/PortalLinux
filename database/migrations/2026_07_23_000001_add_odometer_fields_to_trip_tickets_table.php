<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('trip_tickets')) {
            return;
        }

        Schema::table('trip_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('trip_tickets', 'departure_odometer')) {
                $table->decimal('departure_odometer', 12, 2)->nullable()->after('actual_departure_datetime');
            }

            if (!Schema::hasColumn('trip_tickets', 'return_odometer')) {
                $table->decimal('return_odometer', 12, 2)->nullable()->after('actual_return_datetime');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('trip_tickets')) {
            return;
        }

        Schema::table('trip_tickets', function (Blueprint $table) {
            foreach (['return_odometer', 'departure_odometer'] as $column) {
                if (Schema::hasColumn('trip_tickets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
