<?php

use App\Models\Identity\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $username = (string) config('services.sso.admin_username', 'elearning');
            $email = 'elearning@prestige.local';
            $legacyEmail = 'genz.developt@gmail.com';

            $elearning = User::withTrashed()->where('nip', $username)->first();
            $legacy = User::withTrashed()->where('email', $legacyEmail)->first();
            $emailOwner = User::withTrashed()->where('email', $email)->first();

            // Fresh databases get this account from UserSeeder after migrations.
            if (! $elearning && ! $legacy && ! $emailOwner) {
                return;
            }

            if ($emailOwner && $elearning && $emailOwner->id !== $elearning->id) {
                throw new RuntimeException('Email internal eLearning sudah digunakan akun lain.');
            }

            if (! $elearning) {
                $elearning = $emailOwner ?? User::create([
                    'name' => 'eLearning',
                    'email' => $email,
                    'nip' => $username,
                    'auth_source' => User::AUTH_SOURCE_PRESTIGE,
                    'password' => Hash::make(Str::password(64)),
                ]);
            }

            if ($elearning->auth_source !== User::AUTH_SOURCE_PRESTIGE) {
                throw new RuntimeException('Username Prestige eLearning digunakan akun non-Prestige.');
            }

            if ($elearning->trashed()) {
                $elearning->restore();
            }
            $elearning->update(['name' => 'eLearning', 'email' => $email, 'nip' => $username]);
            $elearning->assignRole(Role::findOrCreate('super_admin', 'web'));

            if ($legacy && $legacy->id !== $elearning->id) {
                $legacy->removeRole('super_admin');
                DB::table('users')->where('id', $legacy->id)->update([
                    'access_token' => null,
                    'refresh_token' => null,
                    'token_expires_at' => null,
                    'password' => Hash::make(Str::password(64)),
                    'deleted_at' => now(),
                ]);
                if (DB::getSchemaBuilder()->hasTable('personal_access_tokens')) {
                    DB::table('personal_access_tokens')
                        ->where('tokenable_type', User::class)
                        ->where('tokenable_id', $legacy->id)
                        ->delete();
                }
                if (DB::getSchemaBuilder()->hasTable('sessions')) {
                    DB::table('sessions')->where('user_id', $legacy->id)->delete();
                }
            }
        });
    }

    public function down(): void
    {
        // Reinstating an obsolete super admin would restore access unexpectedly.
    }
};
