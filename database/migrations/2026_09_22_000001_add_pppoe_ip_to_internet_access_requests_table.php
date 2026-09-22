<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internet_access_requests', function (Blueprint $table) {
            $table->ipAddress('pppoe_ip')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('internet_access_requests', function (Blueprint $table) {
            $table->dropColumn('pppoe_ip');
        });
    }
};
