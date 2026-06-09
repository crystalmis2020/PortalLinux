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

        DB::statement('ALTER TABLE trip_tickets MODIFY ticket_number VARCHAR(50) NULL');
        DB::statement("ALTER TABLE trip_tickets MODIFY status VARCHAR(40) NOT NULL DEFAULT 'pending_details'");

        DB::table('trip_tickets')
            ->where('status', 'pending')
            ->update(['status' => 'pending_details']);

        DB::table('trip_tickets')
            ->where('status', 'declined')
            ->update(['status' => 'rejected']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('trip_tickets')) {
            return;
        }

        DB::table('trip_tickets')
            ->where('status', 'pending_details')
            ->update(['status' => 'pending']);

        DB::table('trip_tickets')
            ->where('status', 'rejected')
            ->update(['status' => 'declined']);

        DB::statement("ALTER TABLE trip_tickets MODIFY status ENUM('pending','approved','declined') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE trip_tickets MODIFY ticket_number VARCHAR(255) NOT NULL DEFAULT ''");
    }
};
