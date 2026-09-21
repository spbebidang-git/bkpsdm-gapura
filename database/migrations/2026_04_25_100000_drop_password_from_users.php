<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('password');
            });
        }

        // Plain tokens incompatible with encrypted cast — force re-login
        if (Schema::hasColumn('users', 'access_token')) {
            \DB::table('users')->update([
                'access_token' => null,
                'refresh_token' => null,
                'token_expires_at' => null,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('password')->nullable()->after('email');
            });
        }
    }
};
