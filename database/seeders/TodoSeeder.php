<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Todo;

class TodoSeeder extends Seeder
{
    public function run(): void
    {
        // Get existing categories
        $workCategory = Category::where('name', 'Work')->first();
        $personalCategory = Category::where('name', 'Personal')->first();
        $homeCategory = Category::where('name', 'Home')->first();
        
        // Create specific todos
        $todos = [
            [
                'task_name' => 'Complete Laravel project',
                'due_date' => now()->addDays(7),
                'done_on' => null,
                'category_id' => $workCategory->id,
            ],
            [
                'task_name' => 'Review quarterly reports',
                'due_date' => now()->addDays(3),
                'done_on' => null,
                'category_id' => $workCategory->id,
            ],
            [
                'task_name' => 'Buy groceries',
                'due_date' => now()->addDay(),
                'done_on' => null,
                'category_id' => $personalCategory->id,
            ],
            [
                'task_name' => 'Clean the garage',
                'due_date' => now()->addWeek(),
                'done_on' => null,
                'category_id' => $homeCategory->id,
            ],
            [
                'task_name' => 'Call dentist',
                'due_date' => now()->subDays(2), // Overdue task
                'done_on' => null,
                'category_id' => $personalCategory->id,
            ],
            [
                'task_name' => 'Submit expense report',
                'due_date' => now()->subDay(),
                'done_on' => now()->subDays(2), // Completed task
                'category_id' => $workCategory->id,
            ],
        ];

        foreach ($todos as $todoData) {
            Todo::updateOrCreate(
                ['task_name' => $todoData['task_name']], // Find by task name
                $todoData // Update/create with all data
            );
        }
        
        // If you want some random todos as well, create them explicitly:
        $categories = Category::all();
        
        for ($i = 1; $i <= 10; $i++) {
            Todo::updateOrCreate(
                ['task_name' => "Random Task {$i}"],
                [
                    'due_date' => now()->addDays(rand(1, 30)),
                    'done_on' => rand(0, 1) ? now()->subDays(rand(1, 10)) : null,
                    'category_id' => $categories->random()->id,
                ]
            );
        }
    }
}