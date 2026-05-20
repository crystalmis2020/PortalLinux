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
        if (Schema::hasTable('notifications')) {
            return;
        }

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_user_id')->nullable();
            $table->unsignedBigInteger('to_user_id')->nullable();
            $table->unsignedBigInteger('section_to')->nullable();
            $table->unsignedBigInteger('report_id');
            $table->string('title');
            $table->string('message');
            $table->enum('is_read', ['Yes', 'No'])->default('No');
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

