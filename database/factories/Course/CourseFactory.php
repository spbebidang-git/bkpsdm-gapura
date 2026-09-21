<?php

namespace Database\Factories\Course;

use App\Models\Course\Category;
use App\Models\Course\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'category_id' => Category::query()->inRandomOrder()->value('id')
                ?? Category::factory(),
            'access_mode' => fake()->randomElement(['open', 'scheduled']),
            'default_access_hours' => fake()->optional(0.6)->numberBetween(24, 168),
            'title' => rtrim($title, '.'),
            'slug' => str()->slug($title).'-'.fake()->unique()->numerify('###'),
            'short_description' => fake()->paragraph(2),
            'description' => '<p>'.fake()->paragraphs(3, true).'</p>',
            'thumbnail' => '',
            'jam_pelajaran' => fake()->randomElement([4, 8, 16, 24, 32]),
            'teaser_video' => null,
            'outline_topic' => fake()->optional()->text(120),
            'status' => 'publish',
            'is_locked' => false,
        ];
    }

    public function open(): static
    {
        return $this->state(fn () => [
            'access_mode' => 'open',
            'default_access_hours' => 72,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn () => [
            'access_mode' => 'scheduled',
            'default_access_hours' => null,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft']);
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => 'publish']);
    }
}
