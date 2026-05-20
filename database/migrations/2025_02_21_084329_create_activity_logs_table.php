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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('User who performed the action');
            $table->string('action')->comment('Type of action performed');
            $table->text('details')->nullable()->comment('Description of the action');
            $table->string('model_type')->nullable()->comment('Eloquent Model type');
            $table->unsignedBigInteger('model_id')->nullable()->comment('ID of the model affected');
            $table->ipAddress('ip_address')->nullable()->comment('IP address of the user');
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
