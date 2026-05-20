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
        Schema::create('departments', function (Blueprint $table) {
            $table->id(); // Equivalent to `id` column with auto-increment
            $table->string('code', 10)->nullable(); // Equivalent to `code` varchar(10)
            $table->string('name', 150)->nullable(); // Equivalent to `name` varchar(150)
            $table->timestamp('created_at')->nullable(); // Equivalent to `createdAt` timestamp
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate(); // Equivalent to `updatedAt`
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
