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
        if (Schema::hasTable('network_hosts')) {
            return;
        }

        Schema::create('network_hosts', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address')->unique();
            $table->string('server_name')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['online', 'offline'])->default('offline')->index();
            $table->timestamp('last_check')->nullable()->index();
            $table->foreignId('added_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('network_hosts');
    }
};
