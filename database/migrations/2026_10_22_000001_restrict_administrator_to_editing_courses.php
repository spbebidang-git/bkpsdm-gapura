<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Administrator hanya membantu memperbaiki kelas yang sudah disusun pemateri.
     * Hak membuat/menghapus kelas dan kegiatan dicabut.
     */
    private array $revoked = [
        'course.create',
        'course.delete',
        'course.activity.create',
        'course.activity.delete',
    ];

    public function up(): void
    {
        $roleIds = DB::table('roles')->where(['name' => 'administrator', 'guard_name' => 'web'])->pluck('id');

        if ($roleIds->isEmpty()) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return;
        }

        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', $this->revoked)
            ->pluck('id');

        DB::table('role_has_permissions')
            ->whereIn('role_id', $roleIds)
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Hak akses tidak dikembalikan otomatis; jalankan PermissionSeeder bila perlu.
    }
};
