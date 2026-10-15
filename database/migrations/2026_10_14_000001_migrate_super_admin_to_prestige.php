<?php

use App\Models\Identity\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $email = (string) config('services.sso.super_admin_email', 'genz.developt@gmail.com');
        $username = (string) config('services.sso.admin_username', 'elearning');
        $admin = DB::table('users')->where('email', $email)->first();
        $conflict = DB::table('users')->where('nip', $username)
            ->when($admin, fn ($query) => $query->where('id', '!=', $admin->id))
            ->first();

        if ($conflict) {
            throw new RuntimeException("Prestige username '{$username}' sudah digunakan oleh akun lain (ID {$conflict->id}); migrasi superadmin dibatalkan.");
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('auth_source')->default('sso')->change();
        });

        if ($admin) {
            DB::table('users')->where('id', $admin->id)->update([
                'nip' => $username,
                'auth_source' => User::AUTH_SOURCE_PRESTIGE,
                'password' => bcrypt(Str::password(64)),
                'access_token' => null,
                'refresh_token' => null,
                'token_expires_at' => null,
            ]);
        }
    }

    public function down(): void
    {
        $email = (string) config('services.sso.super_admin_email', 'genz.developt@gmail.com');
        DB::table('users')->where('email', $email)->where('auth_source', User::AUTH_SOURCE_PRESTIGE)
            ->update(['auth_source' => User::AUTH_SOURCE_LOCAL]);
    }
};
