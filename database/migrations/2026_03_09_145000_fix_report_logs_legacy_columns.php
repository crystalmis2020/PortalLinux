<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('report_logs')) {
            return;
        }

        Schema::table('report_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('report_logs', 'report_id')) {
                $table->unsignedBigInteger('report_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('report_logs', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('report_id');
            }
            if (!Schema::hasColumn('report_logs', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('status');
            }
            if (!Schema::hasColumn('report_logs', 'is_child')) {
                $table->string('is_child', 2)->nullable()->after('parent_id');
            }
            if (!Schema::hasColumn('report_logs', 'created_at')) {
                $table->timestamp('created_at')->nullable()->after('is_child');
            }
            if (!Schema::hasColumn('report_logs', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });

        // Backfill snake_case columns from legacy camelCase columns when present.
        $columns = collect(DB::select('SHOW COLUMNS FROM report_logs'))->pluck('Field')->all();

        if (in_array('reportId', $columns, true)) {
            DB::statement('UPDATE `report_logs` SET `report_id` = `reportId` WHERE `report_id` IS NULL');
        }
        if (in_array('userId', $columns, true)) {
            DB::statement('UPDATE `report_logs` SET `user_id` = `userId` WHERE `user_id` IS NULL');
        }
        if (in_array('parentId', $columns, true)) {
            DB::statement('UPDATE `report_logs` SET `parent_id` = `parentId` WHERE `parent_id` IS NULL');
        }
        if (in_array('isChild', $columns, true)) {
            DB::statement('UPDATE `report_logs` SET `is_child` = `isChild` WHERE `is_child` IS NULL');
        }
        if (in_array('createdAt', $columns, true)) {
            DB::statement('UPDATE `report_logs` SET `created_at` = `createdAt` WHERE `created_at` IS NULL');
        }
        if (in_array('updatedAt', $columns, true)) {
            DB::statement('UPDATE `report_logs` SET `updated_at` = `updatedAt` WHERE `updated_at` IS NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('report_logs')) {
            return;
        }

        Schema::table('report_logs', function (Blueprint $table) {
            if (Schema::hasColumn('report_logs', 'report_id')) {
                $table->dropColumn('report_id');
            }
            if (Schema::hasColumn('report_logs', 'user_id')) {
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('report_logs', 'parent_id')) {
                $table->dropColumn('parent_id');
            }
            if (Schema::hasColumn('report_logs', 'is_child')) {
                $table->dropColumn('is_child');
            }
            if (Schema::hasColumn('report_logs', 'created_at')) {
                $table->dropColumn('created_at');
            }
            if (Schema::hasColumn('report_logs', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }
};

