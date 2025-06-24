<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Note>
 */
class NoteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Note::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $colors = [
            '#ffffff', // White
            '#f28b82', // Red
            '#fbbc04', // Orange
            '#fff475', // Yellow
            '#ccff90', // Green
            '#a7ffeb', // Teal
            '#cbf0f8', // Blue
            '#aecbfa', // Dark Blue
            '#d7aefb', // Purple
            '#fdcfe8', // Pink
            '#e6c9a8', // Brown
            '#e8eaed', // Gray
        ];

        return [
            'user_id' => User::factory(),
            'title' => $this->faker->optional(0.7)->sentence(3),
            'content' => $this->faker->optional(0.9)->paragraphs(
                $this->faker->numberBetween(1, 5),
                true
            ),
            'color' => $this->faker->randomElement($colors),
            'is_pinned' => $this->faker->boolean(20), // 20% chance of being pinned
            'is_archived' => $this->faker->boolean(10), // 10% chance of being archived
            'archived_at' => function (array $attributes) {
                return $attributes['is_archived'] ? $this->faker->dateTimeBetween('-1 month', 'now') : null;
            },
            'sort_order' => $this->faker->numberBetween(0, 100),
            'labels' => $this->faker->optional(0.3)->randomElements([
                'work',
                'personal',
                'shopping',
                'ideas',
                'todo',
                'important',
                'notes',
                'reminders'
            ], $this->faker->numberBetween(1, 3)),
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'updated_at' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            },
        ];
    }

    /**
     * Indicate that the note is pinned.
     */
    public function pinned(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_pinned' => true,
        ]);
    }

    /**
     * Indicate that the note is archived.
     */
    public function archived(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_archived' => true,
            'archived_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the note is deleted (soft deleted).
     */
    public function deleted(): static
    {
        return $this->state(fn(array $attributes) => [
            'deleted_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Create a note with only a title.
     */
    public function titleOnly(): static
    {
        return $this->state(fn(array $attributes) => [
            'title' => $this->faker->sentence(4),
            'content' => null,
        ]);
    }

    /**
     * Create a note with only content.
     */
    public function contentOnly(): static
    {
        return $this->state(fn(array $attributes) => [
            'title' => null,
            'content' => $this->faker->paragraphs(2, true),
        ]);
    }

    /**
     * Create an empty note.
     */
    public function empty(): static
    {
        return $this->state(fn(array $attributes) => [
            'title' => null,
            'content' => null,
        ]);
    }

    /**
     * Create a note with a specific color.
     */
    public function withColor(string $color): static
    {
        return $this->state(fn(array $attributes) => [
            'color' => $color,
        ]);
    }

    /**
     * Create a note with specific labels.
     */
    public function withLabels(array $labels): static
    {
        return $this->state(fn(array $attributes) => [
            'labels' => $labels,
        ]);
    }

    /**
     * Create a shopping list note.
     */
    public function shoppingList(): static
    {
        $items = [
            'Milk',
            'Bread',
            'Eggs',
            'Apples',
            'Chicken',
            'Rice',
            'Pasta',
            'Tomatoes',
            'Onions',
            'Cheese',
            'Yogurt',
            'Bananas'
        ];

        $selectedItems = $this->faker->randomElements($items, $this->faker->numberBetween(3, 8));
        $content = "• " . implode("\n• ", $selectedItems);

        return $this->state(fn(array $attributes) => [
            'title' => 'Shopping List',
            'content' => $content,
            'labels' => ['shopping'],
        ]);
    }

    /**
     * Create a todo list note.
     */
    public function todoList(): static
    {
        $tasks = [
            'Call dentist',
            'Review project proposal',
            'Buy groceries',
            'Clean the house',
            'Exercise',
            'Read book',
            'Pay bills',
            'Plan weekend trip',
            'Organize photos',
            'Update resume'
        ];

        $selectedTasks = $this->faker->randomElements($tasks, $this->faker->numberBetween(3, 6));
        $content = "";

        foreach ($selectedTasks as $task) {
            $done = $this->faker->boolean(30); // 30% chance task is done
            $content .= ($done ? "✓ " : "□ ") . $task . "\n";
        }

        return $this->state(fn(array $attributes) => [
            'title' => 'Todo',
            'content' => trim($content),
            'labels' => ['todo'],
        ]);
    }

    /**
     * Create a meeting notes note.
     */
    public function meetingNotes(): static
    {
        return $this->state(fn(array $attributes) => [
            'title' => 'Meeting Notes - ' . $this->faker->date(),
            'content' => "Attendees: " . implode(', ', $this->faker->words(3)) . "\n\n" .
                "Key Points:\n" .
                "• " . $this->faker->sentence() . "\n" .
                "• " . $this->faker->sentence() . "\n" .
                "• " . $this->faker->sentence() . "\n\n" .
                "Action Items:\n" .
                "• " . $this->faker->sentence() . "\n" .
                "• " . $this->faker->sentence(),
            'labels' => ['work', 'meetings'],
        ]);
    }

    /**
     * Create a recipe note.
     */
    public function recipe(): static
    {
        $recipes = [
            'Chocolate Chip Cookies',
            'Chicken Stir Fry',
            'Spaghetti Carbonara',
            'Banana Bread',
            'Caesar Salad',
            'Beef Tacos',
            'Vegetable Soup'
        ];

        return $this->state(fn(array $attributes) => [
            'title' => $this->faker->randomElement($recipes),
            'content' => "Ingredients:\n" .
                "• " . implode("\n• ", $this->faker->words(6)) . "\n\n" .
                "Instructions:\n" .
                "1. " . $this->faker->sentence() . "\n" .
                "2. " . $this->faker->sentence() . "\n" .
                "3. " . $this->faker->sentence(),
            'labels' => ['recipes'],
            'color' => '#fff475', // Yellow for recipes
        ]);
    }
}