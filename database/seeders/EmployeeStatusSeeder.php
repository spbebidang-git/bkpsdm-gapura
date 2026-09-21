<?php

namespace Database\Seeders;

use App\Models\Identity\EmployeeStatus;
use Illuminate\Database\Seeder;

class EmployeeStatusSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['CPNS', 'PNS', 'PPPK', 'THL'] as $title) {
            EmployeeStatus::firstOrCreate(['title' => $title]);
        }
    }
}
