<?php

namespace Database\Seeders;

use App\Models\Course\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $activities = [
            ['code' => '3', 'title' => 'Diklat Teknis'],
            ['code' => '5', 'title' => 'Pelatihan Manajerial'],
            ['code' => '6', 'title' => 'Pelatihan Sosial Kultural'],
            ['code' => '7', 'title' => 'Sosialisasi'],
            ['code' => '8', 'title' => 'Bimbingan Teknis'],
            ['code' => '9', 'title' => 'Seminar'],
            ['code' => '10', 'title' => 'Magang'],
            ['code' => '4', 'title' => 'Workshop'],
            ['code' => '2', 'title' => 'Diklat Fungsional'],
        ];

        foreach ($activities as $activity) {
            Category::updateOrCreate(
                ['code' => $activity['code']],
                ['title' => $activity['title']]
            );
        }
    }
}
