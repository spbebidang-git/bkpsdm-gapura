<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $permissions = [
        'dashboard.view', 'course.view', 'course.create', 'course.update', 'course.delete',
        'course.activity.manage', 'course.participant.view', 'result.view', 'trainer.manage',
        'participant.view', 'certificate.manage', 'import.manage', 'review.manage', 'help.manage',
        'category.manage', 'user.manage', 'role.manage', 'permission.manage', 'setting.manage', 'log.view',
    ];

    public function up(): void
    {
        foreach ($this->permissions as $name) {
            DB::table('permissions')->insertOrIgnore(['name' => $name, 'guard_name' => 'web']);
        }

        $administrator = [
            'dashboard.view', 'course.view', 'course.create', 'course.update', 'course.delete',
            'course.activity.manage', 'course.participant.view', 'result.view', 'trainer.manage',
            'participant.view', 'certificate.manage', 'import.manage', 'review.manage', 'help.manage',
            'category.manage',
        ];
        $trainer = [
            'dashboard.view', 'course.view', 'course.create', 'course.update',
            'course.activity.manage', 'course.participant.view', 'result.view',
        ];

        $roles = DB::table('roles')->where('guard_name', 'web')->pluck('id', 'name');
        $permissionIds = DB::table('permissions')->where('guard_name', 'web')->pluck('id', 'name');

        foreach (['super_admin' => $this->permissions, 'administrator' => $administrator, 'trainer' => $trainer, 'participant' => ['dashboard.view']] as $role => $names) {
            if (! isset($roles[$role])) {
                continue;
            }

            DB::table('role_has_permissions')->where('role_id', $roles[$role])->delete();

            foreach ($names as $name) {
                if (isset($permissionIds[$name])) {
                    DB::table('role_has_permissions')->insertOrIgnore([
                        'permission_id' => $permissionIds[$name],
                        'role_id' => $roles[$role],
                    ]);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Permission assignments are intentionally retained on rollback.
    }
};
