<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            [
                'name' => 'Admin',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Instructor',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Participant',
                'guard_name' => 'web'
            ],
        ];

        Role::insert($roles);
    }
}
