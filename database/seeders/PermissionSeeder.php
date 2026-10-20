<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public const ALL = [
        'dashboard.view', 'course.view', 'course.create', 'course.update', 'course.delete',
        'course.activity.manage', 'course.participant.view', 'result.view', 'trainer.manage',
        'participant.view', 'participant.manage', 'certificate.manage', 'import.manage', 'review.manage', 'help.manage',
        'category.view', 'category.manage', 'opd.manage', 'employee-status.manage', 'user.manage', 'role.manage', 'permission.manage',
        'setting.manage', 'notification-template.manage', 'log.view', 'backup.download',
    ];

    public function run(): void
    {
        foreach (self::ALL as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $all = Permission::where('guard_name', 'web')->get();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web'])
            ->syncPermissions($all);

        $administrator = Role::firstOrCreate(['name' => 'administrator', 'guard_name' => 'web']);
        $administrator->syncPermissions([
            'dashboard.view', 'course.view', 'course.create', 'course.update', 'course.delete',
            'course.activity.manage', 'course.participant.view', 'result.view', 'trainer.manage',
            'participant.view', 'participant.manage', 'certificate.manage', 'import.manage', 'review.manage', 'help.manage',
            'category.view', 'category.manage', 'opd.manage', 'employee-status.manage',
        ]);

        Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web'])
            ->syncPermissions([
                'dashboard.view', 'course.view', 'course.create', 'course.update',
                'course.activity.manage', 'course.participant.view', 'result.view', 'category.view',
            ]);

        Role::firstOrCreate(['name' => 'participant', 'guard_name' => 'web'])
            ->syncPermissions(['dashboard.view']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
