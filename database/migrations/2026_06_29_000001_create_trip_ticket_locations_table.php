<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_ticket_locations', function (Blueprint $table) {
            $table->id();
            $table->string('island_group_code', 40);
            $table->string('island_group_name', 100);
            $table->string('region_code', 20);
            $table->string('region_name', 150);
            $table->string('province_code', 20);
            $table->string('province_name', 150);
            $table->string('city_municipality_code', 20)->unique();
            $table->string('city_municipality_name', 150);
            $table->string('psgc_10_digit_code', 20)->nullable()->index();
            $table->string('destination', 255)->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['island_group_code', 'region_name']);
            $table->index(['region_name', 'province_name']);
            $table->index(['province_name', 'city_municipality_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_ticket_locations');
    }
};
