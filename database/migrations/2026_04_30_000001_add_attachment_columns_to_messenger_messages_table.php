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
        if (!Schema::hasColumn('messenger_messages', 'attachment_original_name')) {
            Schema::table('messenger_messages', function (Blueprint $table) {
                $table->string('attachment_original_name')->nullable()->after('body');
            });
        }

        if (!Schema::hasColumn('messenger_messages', 'attachment_file_path')) {
            Schema::table('messenger_messages', function (Blueprint $table) {
                $table->string('attachment_file_path')->nullable()->after('attachment_original_name');
            });
        }

        if (!Schema::hasColumn('messenger_messages', 'attachment_mime_type')) {
            Schema::table('messenger_messages', function (Blueprint $table) {
                $table->string('attachment_mime_type')->nullable()->after('attachment_file_path');
            });
        }

        if (!Schema::hasColumn('messenger_messages', 'attachment_size_bytes')) {
            Schema::table('messenger_messages', function (Blueprint $table) {
                $table->unsignedBigInteger('attachment_size_bytes')->nullable()->after('attachment_mime_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = array_values(array_filter([
            Schema::hasColumn('messenger_messages', 'attachment_original_name') ? 'attachment_original_name' : null,
            Schema::hasColumn('messenger_messages', 'attachment_file_path') ? 'attachment_file_path' : null,
            Schema::hasColumn('messenger_messages', 'attachment_mime_type') ? 'attachment_mime_type' : null,
            Schema::hasColumn('messenger_messages', 'attachment_size_bytes') ? 'attachment_size_bytes' : null,
        ]));

        if ($columns !== []) {
            Schema::table('messenger_messages', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
