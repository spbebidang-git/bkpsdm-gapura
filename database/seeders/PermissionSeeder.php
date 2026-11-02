<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public const ALL = [
        'dashboard.view',
        'course.view', 'course.create', 'course.edit', 'course.delete',
        'course.activity.view', 'course.activity.create', 'course.activity.edit', 'course.activity.delete',
        'course.participant.view', 'result.view',
        'participant.view', 'participant.create', 'participant.edit', 'participant.delete',
        'certificate.view', 'certificate.create', 'certificate.edit', 'certificate.delete',
        'import.view', 'import.create', 'review.view', 'review.edit', 'help.view', 'help.create', 'help.edit', 'help.delete',
        'category.view', 'category.create', 'category.edit', 'category.delete', 'category.sync',
        'opd.view', 'opd.sync',
        'trainer.view', 'trainer.create', 'trainer.edit', 'trainer.delete',
        'instructor.view', 'instructor.create', 'instructor.edit', 'instructor.delete',
        'employee-status.view', 'employee-status.create', 'employee-status.edit', 'employee-status.delete',
        'user.view', 'user.create', 'user.edit', 'user.delete',
        'role.view', 'role.create', 'role.edit', 'role.delete',
        'permission.view', 'permission.create', 'permission.edit', 'permission.delete',
        'setting.view', 'setting.edit',
        'notification-template.view', 'notification-template.edit', 'notification-template.test',
        'log.view', 'backup.download',
    ];

    public function run(): void
    {
        // Reconcile the web permission set so removed legacy permissions (for
        // example `*.manage` and `course.update`) are removed on every seed.
        Permission::where('guard_name', 'web')
            ->whereNotIn('name', self::ALL)
            ->get()
            ->each
            ->delete();

        foreach (self::ALL as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $all = Permission::where('guard_name', 'web')->get();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web'])
            ->syncPermissions($all);

        $administrator = Role::firstOrCreate(['name' => 'administrator', 'guard_name' => 'web']);
        // Administrator hanya membantu memeriksa dan memperbaiki data kelas yang
        // sudah disusun pemateri: boleh lihat + edit, tidak boleh membuat/menghapus
        // kelas maupun kegiatan.
        $administrator->syncPermissions([
            'dashboard.view', 'course.view', 'course.edit',
            'course.activity.view', 'course.activity.edit',
            'course.participant.view', 'result.view',
            'participant.view', 'participant.create', 'participant.edit', 'participant.delete',
            'certificate.view', 'certificate.create', 'certificate.edit', 'certificate.delete',
            'import.view', 'import.create', 'review.view', 'review.edit', 'help.view', 'help.create', 'help.edit', 'help.delete',
            'category.view', 'category.create', 'category.edit', 'category.delete', 'category.sync',
            'opd.view', 'opd.sync',
            'trainer.view', 'trainer.create', 'trainer.edit', 'trainer.delete',
            'instructor.view', 'instructor.create', 'instructor.edit', 'instructor.delete',
            'employee-status.view', 'employee-status.create', 'employee-status.edit', 'employee-status.delete',
        ]);

        Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web'])
            ->syncPermissions([
                'dashboard.view', 'course.view', 'course.create', 'course.edit',
                'course.activity.view', 'course.activity.create', 'course.activity.edit', 'course.activity.delete',
                'course.participant.view', 'result.view', 'category.view',
            ]);

        Role::firstOrCreate(['name' => 'participant', 'guard_name' => 'web'])
            ->syncPermissions(['dashboard.view']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
