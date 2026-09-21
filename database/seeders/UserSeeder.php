<?php

namespace Database\Seeders;

use App\Models\Identity\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.test'],
            ['name' => 'Admin', 'nip' => 'admin-demo']
        );
        $instructor = User::firstOrCreate(
            ['email' => 'instructor@example.test'],
            ['name' => 'Instructor Name', 'nip' => 'instructor-demo']
        );
        $participant = User::firstOrCreate(
            ['email' => 'participant@example.test'],
            ['name' => 'Participant Name', 'nip' => '199001012020121001']
        );

        if (! $admin->hasRole('Admin')) {
            $admin->assignRole('Admin');
        }
        if (! $instructor->hasRole('Instructor')) {
            $instructor->assignRole('Instructor');
        }
        if (! $participant->hasRole('Participant')) {
            $participant->assignRole('Participant');
        }
    }
}
