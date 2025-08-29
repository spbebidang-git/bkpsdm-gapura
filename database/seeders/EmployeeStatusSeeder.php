<?php

namespace Database\Seeders;

use App\Models\EmployeeStatus;
use Illuminate\Database\Seeder;

class EmployeeStatusSeeder extends Seeder
{
    public function run()
    {
        $employeeStatus = [
            [
                'title' => 'CPNS'
            ],
            [
                'title' => 'PNS'
            ],
            [
                'title' => 'PPPK'
            ],
            [
                'title' => 'THL'
            ],
        ];

        EmployeeStatus::insert($employeeStatus);
    }
}
