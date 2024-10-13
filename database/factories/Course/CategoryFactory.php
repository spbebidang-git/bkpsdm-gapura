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
            'title' => $title,
            'slug' => str()->slug($title),
            'is_primary' => $this->faker->boolean
        ];
    }
}
