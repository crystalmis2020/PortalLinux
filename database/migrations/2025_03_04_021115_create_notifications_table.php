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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_user_id')->nullable(); // Sender (can be null)
            $table->unsignedBigInteger('to_user_id')->nullable(); // Receiver (if assigned to a specific user)
            $table->unsignedBigInteger('section_to')->nullable(); // The section the report is addressed to
            $table->unsignedBigInteger('report_id');
            $table->string('title');
            $table->string('message');
            $table->enum('is_read', ['Yes', 'No'])->default('No'); // Add ENUM column
            $table->timestamps();

            $table->foreign('from_user_id')->references('id')->on('users')->onDelete('NO ACTION');
            $table->foreign('to_user_id')->references('id')->on('users')->onDelete('NO ACTION');
            $table->foreign('section_to')->references('id')->on('sections')->onDelete('NO ACTION');
            $table->foreign('report_id')->references('id')->on('reports')->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
