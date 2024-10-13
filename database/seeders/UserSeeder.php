<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => '$2y$10$Gy9RD4HOBjXzgUIcFie3i.epAH3iMnlShgafDoFf8gb3mbH9vv9tO'
        ]);

        $instructor = User::create([
            'name' => 'Instructor Name',
            'email' => 'instructor@example.test',
            'password' => '$2y$10$Gy9RD4HOBjXzgUIcFie3i.epAH3iMnlShgafDoFf8gb3mbH9vv9tO'
        ]);

        $participant = User::create([
            'name' => 'Participant Name',
            'email' => 'participant@example.test',
            'password' => '$2y$10$Gy9RD4HOBjXzgUIcFie3i.epAH3iMnlShgafDoFf8gb3mbH9vv9tO'
        ]);

        $admin->assignRole('Admin');
        $instructor->assignRole('Instructor');
        $participant->assignRole('Participant');
    }
}
