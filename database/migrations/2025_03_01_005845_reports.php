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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_address_to')->nullable(false);
            $table->unsignedBigInteger('section_address_to')->nullable(false);
            $table->unsignedBigInteger('department_address_from')->nullable(false);
            $table->unsignedBigInteger('section_address_from')->nullable(false);
            $table->unsignedBigInteger('issue_id')->nullable(false);
            $table->unsignedBigInteger('issue_sub_category_id')->nullable()->default(null);
            $table->unsignedBigInteger('assigned_by')->nullable()->default(null);
            $table->unsignedBigInteger('assigned_to')->nullable()->default(null);
            $table->unsignedBigInteger('reported_by')->nullable(false);
            $table->longText('issue')->nullable(false);
            $table->string('contact_number')->nullable()->default(null);
            $table->enum('status', ['new', 'assigned', 'in progress', 'resolved', 'closed', 'unassigned'])->default('new');
            $table->unsignedBigInteger('parent_report_id')->nullable()->default(null);
            $table->unsignedBigInteger('child_number')->nullable()->default(null);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
