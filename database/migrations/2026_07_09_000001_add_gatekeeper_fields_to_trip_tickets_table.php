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
            if (!Schema::hasColumn('trip_tickets', 'departure_recorded_by')) {
                $table->foreignId('departure_recorded_by')->nullable()->after('actual_departure_datetime')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('trip_tickets', 'departure_recorded_at')) {
                $table->timestamp('departure_recorded_at')->nullable()->after('departure_recorded_by');
            }

            if (!Schema::hasColumn('trip_tickets', 'return_recorded_by')) {
                $table->foreignId('return_recorded_by')->nullable()->after('actual_return_datetime')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('trip_tickets', 'return_recorded_at')) {
                $table->timestamp('return_recorded_at')->nullable()->after('return_recorded_by');
            }

            if (!Schema::hasColumn('trip_tickets', 'gatekeeper_departure_remarks')) {
                $table->text('gatekeeper_departure_remarks')->nullable()->after('return_recorded_at');
            }

            if (!Schema::hasColumn('trip_tickets', 'gatekeeper_return_remarks')) {
                $table->text('gatekeeper_return_remarks')->nullable()->after('gatekeeper_departure_remarks');
            }

            if (!Schema::hasColumn('trip_tickets', 'qr_token')) {
                $table->string('qr_token', 80)->nullable()->unique()->after('gatekeeper_return_remarks');
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
            foreach (['departure_recorded_by', 'return_recorded_by'] as $column) {
                if (Schema::hasColumn('trip_tickets', $column)) {
                    $table->dropForeign([$column]);
                }
            }

            foreach ([
                'qr_token',
                'gatekeeper_return_remarks',
                'gatekeeper_departure_remarks',
                'return_recorded_at',
                'return_recorded_by',
                'departure_recorded_at',
                'departure_recorded_by',
            ] as $column) {
                if (Schema::hasColumn('trip_tickets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
