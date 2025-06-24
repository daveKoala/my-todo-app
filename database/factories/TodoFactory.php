<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class TodoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'task_name' => $this->faker->sentence(4),
            'done_on' => $this->faker->optional(0.3)->dateTimeBetween('-30 days', 'now'),
            'due_date' => $this->faker->optional(0.8)->dateTimeBetween('now', '+30 days'),
            'category_id' => Category::inRandomOrder()->first()?->id ?? Category::factory(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'done_on' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'done_on' => null,
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'done_on' => null,
            'due_date' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }
}