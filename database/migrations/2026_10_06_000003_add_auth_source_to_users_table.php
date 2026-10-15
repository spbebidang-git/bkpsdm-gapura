<?php

use App\Models\Identity\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'auth_source')) {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('auth_source', ['local', 'sso'])->default('sso')->after('password')->index();
            });
        }

        // Existing identities are assumed to be Kedaton identities unless they
        // are responsible for GAPURA administration itself.
        DB::table('users')->update(['auth_source' => User::AUTH_SOURCE_SSO]);

        if (Schema::hasTable('model_has_roles') && Schema::hasTable('roles')) {
            $superAdminIds = DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('roles.name', 'super_admin')
                ->where('roles.guard_name', 'web')
                ->where('model_has_roles.model_type', User::class)
                ->pluck('model_has_roles.model_id');

            if ($superAdminIds->isNotEmpty()) {
                DB::table('users')->whereIn('id', $superAdminIds)->update([
                    'auth_source' => User::AUTH_SOURCE_LOCAL,
                ]);
            }
        }

        DB::table('users')
            ->where('email', 'genz.developt@gmail.com')
            ->update(['auth_source' => User::AUTH_SOURCE_LOCAL]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'auth_source')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['auth_source']);
                $table->dropColumn('auth_source');
            });
        }
    }
};
