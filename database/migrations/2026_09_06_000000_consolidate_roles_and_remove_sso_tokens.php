<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $adminRole = DB::table('roles')
                ->where('name', 'Admin')
                ->where('guard_name', 'web')
                ->first();
            $participantRole = DB::table('roles')
                ->where('name', 'Participant')
                ->where('guard_name', 'web')
                ->first();
            $instructorRole = DB::table('roles')
                ->where('name', 'Instructor')
                ->where('guard_name', 'web')
                ->first();

            if ($adminRole && $instructorRole) {
                $instructorAssignments = DB::table('model_has_roles')
                    ->where('role_id', $instructorRole->id)
                    ->get(['model_id', 'model_type']);

                foreach ($instructorAssignments->chunk(500) as $assignments) {
                    DB::table('model_has_roles')->insertOrIgnore(
                        $assignments->map(fn ($assignment) => [
                            'role_id' => $adminRole->id,
                            'model_id' => $assignment->model_id,
                            'model_type' => $assignment->model_type,
                        ])->all(),
                    );
                }

                DB::table('model_has_roles')->where('role_id', $instructorRole->id)->delete();
                DB::table('role_has_permissions')->where('role_id', $instructorRole->id)->delete();
                DB::table('roles')->where('id', $instructorRole->id)->delete();
            }

            // Admin and Participant must be distinct identities. This also
            // cleans up any legacy role collision from the former SSO recovery flow.
            if ($adminRole && $participantRole) {
                $adminAssignments = DB::table('model_has_roles')
                    ->where('role_id', $adminRole->id)
                    ->get(['model_id', 'model_type'])
                    ->groupBy('model_type');

                foreach ($adminAssignments as $modelType => $assignments) {
                    foreach ($assignments->pluck('model_id')->chunk(500) as $modelIds) {
                        DB::table('model_has_roles')
                            ->where('role_id', $participantRole->id)
                            ->where('model_type', $modelType)
                            ->whereIn('model_id', $modelIds->all())
                            ->delete();
                    }
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'sso_access_token')) {
                $table->dropColumn('sso_access_token');
            }
            if (Schema::hasColumn('users', 'sso_refresh_token')) {
                $table->dropColumn('sso_refresh_token');
            }
            if (Schema::hasColumn('users', 'sso_expires_at')) {
                $table->dropColumn('sso_expires_at');
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'sso_access_token')) {
                $table->text('sso_access_token')->nullable()->after('password');
            }
            if (! Schema::hasColumn('users', 'sso_refresh_token')) {
                $table->text('sso_refresh_token')->nullable()->after('sso_access_token');
            }
            if (! Schema::hasColumn('users', 'sso_expires_at')) {
                $table->timestamp('sso_expires_at')->nullable()->after('sso_refresh_token');
            }
        });

        DB::table('roles')->insertOrIgnore([
            'name' => 'Instructor',
            'guard_name' => 'web',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
