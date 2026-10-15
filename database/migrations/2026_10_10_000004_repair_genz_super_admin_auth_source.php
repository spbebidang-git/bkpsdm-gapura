<?php

use App\Models\Identity\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('email', 'genz.developt@gmail.com')
            ->update(['auth_source' => User::AUTH_SOURCE_LOCAL]);
    }

    public function down(): void
    {
        // Keep the account local when rolling back; reverting it to SSO would
        // unexpectedly lock the seeded super admin out of GAPURA.
    }
};
