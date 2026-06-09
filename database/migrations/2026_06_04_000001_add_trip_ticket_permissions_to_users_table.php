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
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'can_encode_trip_tickets')) {
                $table->boolean('can_encode_trip_tickets')->default(false)->after('is_sudo');
            }

            if (!Schema::hasColumn('users', 'can_approve_trip_tickets')) {
                $table->boolean('can_approve_trip_tickets')->default(false)->after('can_encode_trip_tickets');
            }

            if (!Schema::hasColumn('users', 'can_manage_trip_tickets')) {
                $table->boolean('can_manage_trip_tickets')->default(false)->after('can_approve_trip_tickets');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'can_manage_trip_tickets',
                'can_approve_trip_tickets',
                'can_encode_trip_tickets',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
