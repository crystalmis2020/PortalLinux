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
        if (Schema::hasTable('slot_locators')) {
            return;
        }

        Schema::create('slot_locators', function (Blueprint $table) {
            $table->id();
            $table->string('coordinates');     // Example: 1A, 2B, 3D
            $table->string('items')->nullable(); // <-- now STRING instead of int
            $table->unsignedBigInteger('added_by');
            $table->timestamps();

            $table->foreign('added_by')
                ->references('id')
                ->on('users')
                ->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slot_locators');
    }
};
