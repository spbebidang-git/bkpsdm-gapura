<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->insertOrIgnore(['name' => 'backup.download', 'guard_name' => 'web']);
        $permission = DB::table('permissions')->where(['name' => 'backup.download', 'guard_name' => 'web'])->value('id');
        foreach (DB::table('roles')->where(['name' => 'super_admin', 'guard_name' => 'web'])->pluck('id') as $role) {
            DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permission, 'role_id' => $role]);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void {}
};
