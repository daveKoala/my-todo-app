<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Work', 'description' => 'Work-related tasks and projects'],
            ['name' => 'Personal', 'description' => 'Personal tasks and activities'],
            ['name' => 'Home', 'description' => 'Home maintenance and chores'],
            ['name' => 'Health', 'description' => 'Health and wellness activities'],
            ['name' => 'Shopping', 'description' => 'Shopping lists and errands'],
            ['name' => 'Finance', 'description' => 'Financial planning and budgeting'],
            ['name' => 'Learning', 'description' => 'Educational and skill development'],
        ];

        foreach ($categories as $categoryData) {
            Category::updateOrCreate(
                ['name' => $categoryData['name']], // Find by name
                ['description' => $categoryData['description']] // Update/create with description
            );
        }
    }
}