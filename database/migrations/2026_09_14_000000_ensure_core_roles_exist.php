<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Ensure authorization checks can rely on the application's core roles.
     *
     * A deployment normally runs migrations without seeders.  Without these
     * records, Spatie throws RoleDoesNotExist as soon as an authenticated user
     * reaches middleware that checks the Admin role.
     */
    public function up(): void
    {
        DB::table('roles')->insertOrIgnore([
            ['name' => 'Admin', 'guard_name' => 'web'],
            ['name' => 'Participant', 'guard_name' => 'web'],
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Roles may have been assigned after this migration ran, so they must
        // remain intact when rolling back unrelated schema changes.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
