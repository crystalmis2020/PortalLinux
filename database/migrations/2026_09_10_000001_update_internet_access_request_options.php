<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('internet_access_requests')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            // Keep legacy values valid for historical rows; validation only permits 1h/4h for new requests.
            DB::statement("ALTER TABLE internet_access_requests MODIFY requested_hours ENUM('1h','2h','3h','4h','8h') NOT NULL");
            DB::statement("ALTER TABLE internet_access_requests MODIFY status ENUM('pending','ready','active','expired','failed') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('internet_access_requests') || DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE internet_access_requests MODIFY requested_hours ENUM('1h','2h','3h','8h') NOT NULL");
        DB::statement("ALTER TABLE internet_access_requests MODIFY status ENUM('ready','active','expired','failed') NOT NULL DEFAULT 'ready'");
    }
};
