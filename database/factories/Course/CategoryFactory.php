<?php

namespace Database\Factories\Course;

use App\Models\Course\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->word();

        return [
            'code' => (string) $this->faker->unique()->numberBetween(1, 99),
            'title' => $title,
        ];
    }
}
