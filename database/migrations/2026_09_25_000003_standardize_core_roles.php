<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $map = ['Admin' => 'administrator', 'Instructor' => 'trainer', 'Participant' => 'participant'];

            foreach ($map as $legacy => $canonical) {
                $legacyRole = DB::table('roles')->where(['name' => $legacy, 'guard_name' => 'web'])->first();
                $canonicalRole = DB::table('roles')->where(['name' => $canonical, 'guard_name' => 'web'])->first();

                if (! $legacyRole) {
                    if (! $canonicalRole) {
                        DB::table('roles')->insert(['name' => $canonical, 'guard_name' => 'web']);
                    }
                    continue;
                }

                if (! $canonicalRole) {
                    DB::table('roles')->where('id', $legacyRole->id)->update(['name' => $canonical]);
                    continue;
                }

                DB::table('model_has_roles')->where('role_id', $legacyRole->id)->get()->each(function ($assignment) use ($canonicalRole) {
                    DB::table('model_has_roles')->insertOrIgnore([
                        'role_id' => $canonicalRole->id,
                        'model_id' => $assignment->model_id,
                        'model_type' => $assignment->model_type,
                    ]);
                });
                DB::table('role_has_permissions')->where('role_id', $legacyRole->id)->get()->each(function ($assignment) use ($canonicalRole) {
                    DB::table('role_has_permissions')->insertOrIgnore([
                        'permission_id' => $assignment->permission_id,
                        'role_id' => $canonicalRole->id,
                    ]);
                });
                DB::table('model_has_roles')->where('role_id', $legacyRole->id)->delete();
                DB::table('role_has_permissions')->where('role_id', $legacyRole->id)->delete();
                DB::table('roles')->where('id', $legacyRole->id)->delete();
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
