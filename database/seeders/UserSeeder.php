<?php

namespace Database\Seeders;

use App\Models\Identity\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $username = (string) config('services.sso.admin_username', 'elearning');
        $email = (string) config('services.sso.super_admin_email', 'genz.developt@gmail.com');
        $conflict = DB::table('users')->where('nip', $username)->where('email', '!=', $email)->first();
        if ($conflict) {
            throw new \RuntimeException("Prestige username '{$username}' sudah digunakan akun lain (ID {$conflict->id}).");
        }

        $superAdmin = User::withTrashed()->firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Super Admin',
                'password' => Hash::make(Str::password(64)),
                'auth_source' => User::AUTH_SOURCE_PRESTIGE,
                'nip' => $username,
            ]
        );

        if ($superAdmin->trashed()) {
            $superAdmin->restore();
        }

        $superAdmin->update(['name' => 'Super Admin']);
        $superAdmin->syncRoles(['super_admin']);

        // The Prestige password is never stored locally; this random hash only
        // satisfies Laravel's required password column.
        DB::table('users')->where('id', $superAdmin->id)->update([
            'auth_source' => User::AUTH_SOURCE_PRESTIGE,
            'nip' => $username,
            'password' => Hash::make(Str::password(64)),
        ]);

        $this->seedSampleAccount('admin@example.test', 'Admin Sample', 'administrator');
        $this->seedSampleAccount('trainer@example.test', 'Trainer Sample', 'trainer');
        $this->seedSampleAccount('participant@example.test', 'Participant Sample', 'participant');
    }

    private function seedSampleAccount(string $email, string $name, string $role): void
    {
        $user = User::withTrashed()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('Satu2345!!'),
                'auth_source' => User::AUTH_SOURCE_LOCAL,
            ]
        );

        if ($user->trashed()) {
            $user->restore();
        }

        $user->update([
            'name' => $name,
            'password' => Hash::make('Satu2345!!'),
        ]);
        DB::table('users')->where('id', $user->id)->update([
            'auth_source' => User::AUTH_SOURCE_LOCAL,
        ]);
        $user->syncRoles([$role]);
    }
}
