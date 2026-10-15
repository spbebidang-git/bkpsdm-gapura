<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['super_admin', 'administrator', 'trainer', 'participant'] as $name) {
            DB::table('roles')->insertOrIgnore([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }
    }

    public function down(): void
    {
        // Keep role assignments intact on rollback.
    }
};
