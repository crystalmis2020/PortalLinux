<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('trip_tickets', 'trip_ticket_location_id')) {
                $table->foreignId('trip_ticket_location_id')
                    ->nullable()
                    ->after('destination')
                    ->constrained('trip_ticket_locations')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('trip_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('trip_tickets', 'trip_ticket_location_id')) {
                $table->dropConstrainedForeignId('trip_ticket_location_id');
            }
        });
    }
};
