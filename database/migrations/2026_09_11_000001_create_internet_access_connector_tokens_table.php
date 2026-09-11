<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internet_access_connector_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internet_access_request_id');
            $table->foreign('internet_access_request_id', 'iac_tokens_request_fk')
                ->references('id')
                ->on('internet_access_requests')
                ->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->string('issued_ip', 45)->nullable();
            $table->string('consumed_ip', 45)->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(
                ['internet_access_request_id', 'consumed_at'],
                'iac_tokens_request_consumed_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internet_access_connector_tokens');
    }
};
