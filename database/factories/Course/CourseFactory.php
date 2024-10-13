<?php

namespace Database\Factories\Course;

use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    public function definition(): array
    {
        $title = $this->faker->sentence;

        return [
            'category_id' => \App\Models\Course\Category::factory(),
            'instructor_id' => 2,
            'type' => $this->faker->randomElement(['schedule', 'unschedule']),
            'title' => $title,
            'slug' => str()->slug($title),
            'short_description' => $this->faker->paragraph,
            'description' => $this->faker->paragraph,
            'thumbnail' => $this->faker->imageUrl(640, 480),
            'teaser_video' => $this->faker->url,
            'outline_topic' => $this->faker->text,
            'status' => $this->faker->randomElement(['draft', 'publish']),
        ];
    }
}
