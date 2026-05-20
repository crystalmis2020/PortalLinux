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
        if (Schema::hasTable('personnel_leaves')) {
            return;
        }

        Schema::create('personnel_leaves', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('section_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();

            $table->date('from_date');
            $table->date('to_date');

            $table->string('reason', 255)->nullable();
            $table->string('leave_address', 255)->nullable(); // <— shorter name

            $table->foreignId('encode_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();

            $table->timestamps();

            $table->index(['department_id', 'section_id']);
            $table->index(['from_date', 'to_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personnel_leaves');
    }
};
