<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
        ];
    }

    public function work(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Work',
            'description' => 'Work-related tasks and projects',
        ]);
    }

    public function personal(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Personal',
            'description' => 'Personal tasks and activities',
        ]);
    }
}