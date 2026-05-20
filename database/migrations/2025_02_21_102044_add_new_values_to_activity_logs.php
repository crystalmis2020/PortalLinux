<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('activity_logs', 'new_values')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->json('new_values')->nullable()->after('details')->comment('New or updated values');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('activity_logs', 'new_values')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->dropColumn('new_values');
            });
        }
    }
};
