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
        if (Schema::hasTable('trip_tickets')) {
            return;
        }

        Schema::create('trip_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 50)->nullable()->unique();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->text('purpose');
            $table->string('destination');
            $table->dateTime('requested_start_datetime');
            $table->dateTime('requested_end_datetime');
            $table->text('passengers')->nullable();
            $table->string('contact_number', 50)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->dateTime('actual_departure_datetime')->nullable();
            $table->dateTime('actual_return_datetime')->nullable();
            $table->foreignId('encoded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('encoded_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_remarks')->nullable();
            $table->string('status', 40)->default('pending_details');
            $table->timestamps();

            $table->index(['status', 'requested_start_datetime']);
            $table->index(['requested_by', 'status']);
            $table->index(['department_id', 'section_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_tickets');
    }
};
