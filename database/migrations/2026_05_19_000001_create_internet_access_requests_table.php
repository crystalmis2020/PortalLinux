<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('internet_access_requests')) {
            return;
        }

        Schema::create('internet_access_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('requester_ip')->nullable();
            $table->text('purpose');
            $table->enum('requested_hours', ['1h', '2h', '3h', '8h']);
            $table->unsignedSmallInteger('duration_minutes');
            $table->string('username')->unique();
            $table->string('password');
            $table->string('mikrotik_profile');
            $table->string('mikrotik_reference_id')->nullable();
            $table->enum('status', ['ready', 'active', 'expired', 'failed'])->default('ready')->index();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('last_seen_online_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internet_access_requests');
    }
};
