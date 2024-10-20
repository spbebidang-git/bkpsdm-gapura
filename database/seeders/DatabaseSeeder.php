<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(CategorySeeder::class);

        // \App\Models\Course\Category::factory(5)
        //     ->has(\App\Models\Course\Course::factory()
        //         ->count(3)
        //         ->state(function () use (&$counter) {
        //             $types = ['schedule', 'unschedule', 'schedule'];
        //             $type = $types[$counter % 3];
        //             $counter++;
        //             return ['type' => $type];
        //         }))
        //     ->create();
    }
}
