<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('internet_access_requests')) {
            return;
        }

        Schema::table('internet_access_requests', function (Blueprint $table) {
            $table->text('password')->change();
        });

        DB::table('internet_access_requests')
            ->select(['id', 'password'])
            ->orderBy('id')
            ->chunkById(100, function ($requests): void {
                foreach ($requests as $request) {
                    if ($this->isEncrypted($request->password)) {
                        continue;
                    }

                    DB::table('internet_access_requests')
                        ->where('id', $request->id)
                        ->update(['password' => Crypt::encryptString($request->password)]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('internet_access_requests')) {
            return;
        }

        DB::table('internet_access_requests')
            ->select(['id', 'password'])
            ->orderBy('id')
            ->chunkById(100, function ($requests): void {
                foreach ($requests as $request) {
                    try {
                        $password = Crypt::decryptString($request->password);
                    } catch (DecryptException) {
                        continue;
                    }

                    DB::table('internet_access_requests')
                        ->where('id', $request->id)
                        ->update(['password' => $password]);
                }
            });

        Schema::table('internet_access_requests', function (Blueprint $table) {
            $table->string('password')->change();
        });
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
};
