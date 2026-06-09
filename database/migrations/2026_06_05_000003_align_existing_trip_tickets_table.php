<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

        Schema::table('trip_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('trip_tickets', 'section_id')) {
                $table->foreignId('section_id')->nullable()->after('department_id')->constrained('sections')->nullOnDelete();
            }

            if (!Schema::hasColumn('trip_tickets', 'requested_start_datetime')) {
                $table->dateTime('requested_start_datetime')->nullable()->after('date_to');
            }

            if (!Schema::hasColumn('trip_tickets', 'requested_end_datetime')) {
                $table->dateTime('requested_end_datetime')->nullable()->after('requested_start_datetime');
            }

            if (!Schema::hasColumn('trip_tickets', 'passengers')) {
                $table->text('passengers')->nullable()->after('destination');
            }

            if (!Schema::hasColumn('trip_tickets', 'contact_number')) {
                $table->string('contact_number', 50)->nullable()->after('passengers');
            }

            if (!Schema::hasColumn('trip_tickets', 'remarks')) {
                $table->text('remarks')->nullable()->after('contact_number');
            }

            if (!Schema::hasColumn('trip_tickets', 'actual_departure_datetime')) {
                $table->dateTime('actual_departure_datetime')->nullable()->after('driver_name');
            }

            if (!Schema::hasColumn('trip_tickets', 'actual_return_datetime')) {
                $table->dateTime('actual_return_datetime')->nullable()->after('actual_departure_datetime');
            }

            if (!Schema::hasColumn('trip_tickets', 'encoded_by')) {
                $table->foreignId('encoded_by')->nullable()->after('actual_return_datetime')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('trip_tickets', 'encoded_at')) {
                $table->timestamp('encoded_at')->nullable()->after('encoded_by');
            }

            if (!Schema::hasColumn('trip_tickets', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('encoded_at')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('trip_tickets', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }

            if (!Schema::hasColumn('trip_tickets', 'approval_remarks')) {
                $table->text('approval_remarks')->nullable()->after('approved_at');
            }
        });

        if (Schema::hasColumn('trip_tickets', 'date_from') && Schema::hasColumn('trip_tickets', 'requested_start_datetime')) {
            DB::statement('UPDATE trip_tickets SET requested_start_datetime = date_from WHERE requested_start_datetime IS NULL AND date_from IS NOT NULL');
        }

        if (Schema::hasColumn('trip_tickets', 'date_to') && Schema::hasColumn('trip_tickets', 'requested_end_datetime')) {
            DB::statement('UPDATE trip_tickets SET requested_end_datetime = date_to WHERE requested_end_datetime IS NULL AND date_to IS NOT NULL');
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

        Schema::table('trip_tickets', function (Blueprint $table) {
            foreach ([
                'approval_remarks',
                'approved_at',
                'approved_by',
                'encoded_at',
                'encoded_by',
                'actual_return_datetime',
                'actual_departure_datetime',
                'remarks',
                'contact_number',
                'passengers',
                'requested_end_datetime',
                'requested_start_datetime',
                'section_id',
            ] as $column) {
                if (Schema::hasColumn('trip_tickets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
