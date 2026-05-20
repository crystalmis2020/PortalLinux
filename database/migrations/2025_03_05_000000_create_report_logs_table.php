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
        if (Schema::hasTable('report_logs')) {
            return;
        }

        Schema::create('report_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('report_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->longText('message')->nullable();
            $table->longText('remarks')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('is_child', 2)->nullable();
            $table->timestamps();

            $table->foreign('report_id')->references('id')->on('reports')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('parent_id')->references('id')->on('report_logs')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_logs');
    }
};
