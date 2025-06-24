<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Todo;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // Create categories first
        Category::factory()->work()->create();
        Category::factory()->personal()->create();
        Category::factory(5)->create();

        // Create todos
        Todo::factory(20)->create();

        // Create a demo user if one doesn't exist
        if (!User::where('email', 'demo@example.com')->exists()) {
            User::factory()->create([
                'name' => 'Demo User',
                'email' => 'demo@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        }

        // Create additional test users in development
        if (app()->environment(['local', 'testing'])) {
            // Create a few more users for testing
            User::factory(3)->create();
        }

        $this->call([
            NoteSeeder::class,
        ]);
    }
}