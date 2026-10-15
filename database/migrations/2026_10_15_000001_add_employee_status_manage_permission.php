<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->insertOrIgnore(['name' => 'employee-status.manage', 'guard_name' => 'web']);
        $permission = DB::table('permissions')->where(['name' => 'employee-status.manage', 'guard_name' => 'web'])->value('id');
        $roles = DB::table('roles')->whereIn('name', ['super_admin', 'administrator'])->where('guard_name', 'web')->pluck('id');
        foreach ($roles as $role) DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permission, 'role_id' => $role]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void {}
};
