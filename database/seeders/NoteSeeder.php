<?php
// database/seeders/NoteSeeder.php

namespace Database\Seeders;

use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Seeder;

class NoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first user or create one for testing
        $user = User::first() ?? User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
        ]);

        // Create a variety of realistic notes
        $this->createWelcomeNote($user);
        $this->createShoppingLists($user);
        $this->createTodoLists($user);
        $this->createMeetingNotes($user);
        $this->createRecipes($user);
        $this->createQuickNotes($user);
        $this->createIdeasAndQuotes($user);
        $this->createArchivedNotes($user);
        $this->createTrashedNotes($user);

        $this->command->info("Created notes for user: {$user->email}");
    }

    /**
     * Create a welcome note for new users
     */
    private function createWelcomeNote(User $user): void
    {
        Note::create([
            'user_id' => $user->id,
            'title' => 'Welcome to Keep Clone! 🎉',
            'content' => "Thanks for trying out this Google Keep clone!\n\nHere's what you can do:\n• Create notes by clicking \"Take a note...\"\n• Pin important notes to keep them at the top\n• Change colors to organize your thoughts\n• Archive notes you want to keep but hide\n• Search through all your notes instantly\n\nStart capturing your ideas!",
            'color' => '#fff475',
            'is_pinned' => true,
            'labels' => ['welcome', 'tutorial'],
        ]);
    }

    /**
     * Create shopping list notes
     */
    private function createShoppingLists(User $user): void
    {
        // Weekly groceries
        Note::create([
            'user_id' => $user->id,
            'title' => 'Weekly Groceries',
            'content' => "• Milk (2%)\n• Bread (whole grain)\n• Eggs (dozen)\n• Bananas\n• Chicken breast\n• Broccoli\n• Rice\n• Greek yogurt\n• Olive oil\n• Coffee beans",
            'color' => '#ccff90',
            'is_pinned' => true,
            'labels' => ['shopping', 'groceries'],
        ]);

        // Hardware store
        Note::create([
            'user_id' => $user->id,
            'title' => 'Hardware Store',
            'content' => "• Light bulbs (LED, 60W)\n• Screws (Phillips head)\n• Paint brush (2 inch)\n• Sandpaper\n• Measuring tape\n• WD-40",
            'color' => '#e6c9a8',
            'labels' => ['shopping', 'home improvement'],
        ]);
    }

    /**
     * Create todo list notes
     */
    private function createTodoLists(User $user): void
    {
        // Daily todos
        Note::create([
            'user_id' => $user->id,
            'title' => 'Today\'s Tasks',
            'content' => "✓ Check emails\n✓ Team standup meeting\n□ Review project proposal\n□ Call dentist\n□ Grocery shopping\n□ Gym workout\n□ Read for 30 minutes",
            'color' => '#cbf0f8',
            'is_pinned' => true,
            'labels' => ['todo', 'daily'],
        ]);

        // Weekend plans
        Note::create([
            'user_id' => $user->id,
            'title' => 'Weekend Plans',
            'content' => "Saturday:\n□ Sleep in\n□ Farmers market\n□ Clean the house\n□ Movie night\n\nSunday:\n□ Brunch with friends\n□ Park walk\n□ Meal prep for week\n□ Laundry",
            'color' => '#fdcfe8',
            'labels' => ['todo', 'weekend'],
        ]);
    }

    /**
     * Create meeting notes
     */
    private function createMeetingNotes(User $user): void
    {
        Note::create([
            'user_id' => $user->id,
            'title' => 'Project Kickoff Meeting - ' . now()->subDays(2)->format('M j'),
            'content' => "Attendees: Sarah, Mike, Alex, Jennifer\n\nKey Discussion Points:\n• Project timeline: 8 weeks\n• Budget approved: $50k\n• Technology stack: Laravel + Vue.js\n• Weekly check-ins every Friday\n\nAction Items:\n• Sarah: Create project wireframes (Due: Friday)\n• Mike: Set up development environment\n• Alex: Research third-party integrations\n• Jennifer: Schedule client review meeting\n\nNext Meeting: Friday 2PM",
            'color' => '#ffffff',
            'labels' => ['work', 'meetings'],
        ]);

        Note::create([
            'user_id' => $user->id,
            'title' => 'Q1 Planning Session',
            'content' => "Goals for Q1:\n• Launch new feature set\n• Improve user onboarding\n• Reduce churn by 15%\n• Hire 2 new developers\n\nBudget allocations:\n• Development: 60%\n• Marketing: 25%\n• Operations: 15%\n\nKey milestones:\n• Jan 31: Feature freeze\n• Feb 15: Beta testing\n• Mar 1: Public launch",
            'color' => '#aecbfa',
            'labels' => ['work', 'planning'],
        ]);
    }

    /**
     * Create recipe notes
     */
    private function createRecipes(User $user): void
    {
        Note::create([
            'user_id' => $user->id,
            'title' => 'Grandma\'s Chocolate Chip Cookies 🍪',
            'content' => "Ingredients:\n• 2¼ cups all-purpose flour\n• 1 tsp baking soda\n• 1 tsp salt\n• 1 cup butter, softened\n• ¾ cup sugar\n• ¾ cup brown sugar\n• 2 eggs\n• 2 tsp vanilla\n• 2 cups chocolate chips\n\nInstructions:\n1. Preheat oven to 375°F\n2. Mix dry ingredients\n3. Cream butter and sugars\n4. Add eggs and vanilla\n5. Combine wet and dry ingredients\n6. Fold in chocolate chips\n7. Bake 9-11 minutes\n\nTip: Chill dough for 30 minutes for thicker cookies!",
            'color' => '#fff475',
            'labels' => ['recipes', 'desserts'],
        ]);

        Note::create([
            'user_id' => $user->id,
            'title' => 'Quick Pasta Sauce',
            'content' => "• 1 can crushed tomatoes\n• 3 cloves garlic, minced\n• 1 onion, diced\n• 2 tbsp olive oil\n• 1 tsp dried basil\n• Salt and pepper to taste\n• Fresh parmesan\n\nSauté onion and garlic, add tomatoes and simmer 15 minutes. Perfect for busy weeknights!",
            'color' => '#f28b82',
            'labels' => ['recipes', 'quick meals'],
        ]);
    }

    /**
     * Create quick notes and reminders
     */
    private function createQuickNotes(User $user): void
    {
        Note::create([
            'user_id' => $user->id,
            'title' => null,
            'content' => 'WiFi password: SecureNetwork2024!',
            'color' => '#e8eaed',
            'labels' => ['passwords'],
        ]);

        Note::create([
            'user_id' => $user->id,
            'title' => 'Book Recommendations',
            'content' => "• Atomic Habits - James Clear\n• The Psychology of Money - Morgan Housel\n• Sapiens - Yuval Noah Harari\n• Educated - Tara Westover\n• The Midnight Library - Matt Haig",
            'color' => '#d7aefb',
            'labels' => ['books', 'reading'],
        ]);

        Note::create([
            'user_id' => $user->id,
            'title' => null,
            'content' => 'Dentist appointment: Thursday 3PM\nDr. Smith\'s office: (555) 123-4567',
            'color' => '#a7ffeb',
            'labels' => ['appointments'],
        ]);

        Note::create([
            'user_id' => $user->id,
            'title' => 'Gift Ideas for Mom\'s Birthday',
            'content' => "• Spa day voucher\n• New cookbook (Italian cuisine)\n• Silk scarf\n• Subscription to her favorite magazine\n• Weekend getaway to wine country\n• Custom photo album",
            'color' => '#fdcfe8',
            'labels' => ['gifts', 'family'],
        ]);
    }

    /**
     * Create ideas and quotes
     */
    private function createIdeasAndQuotes(User $user): void
    {
        Note::create([
            'user_id' => $user->id,
            'title' => 'App Ideas 💡',
            'content' => "• Plant care reminder app\n• Local food truck tracker\n• Language exchange platform\n• Habit tracker with social features\n• AR furniture placement tool\n• Collaborative playlist maker\n• Digital recipe box with meal planning",
            'color' => '#fff475',
            'is_pinned' => true,
            'labels' => ['ideas', 'projects'],
        ]);

        Note::create([
            'user_id' => $user->id,
            'title' => 'Inspiring Quotes',
            'content' => "\"The only way to do great work is to love what you do.\" - Steve Jobs\n\n\"Success is not final, failure is not fatal: it is the courage to continue that counts.\" - Winston Churchill\n\n\"The future belongs to those who believe in the beauty of their dreams.\" - Eleanor Roosevelt",
            'color' => '#d7aefb',
            'labels' => ['quotes', 'inspiration'],
        ]);

        Note::create([
            'user_id' => $user->id,
            'title' => 'Travel Bucket List ✈️',
            'content' => "Countries to visit:\n• Japan (cherry blossom season)\n• Iceland (northern lights)\n• New Zealand (hiking trails)\n• Morocco (markets and culture)\n• Norway (fjords)\n• Peru (Machu Picchu)\n• Thailand (temples and beaches)",
            'color' => '#a7ffeb',
            'labels' => ['travel', 'bucket list'],
        ]);
    }

    /**
     * Create some archived notes
     */
    private function createArchivedNotes(User $user): void
    {
        Note::create([
            'user_id' => $user->id,
            'title' => 'Old Project Notes',
            'content' => "These are notes from a completed project. Archived to keep them out of the way but still searchable if needed.",
            'color' => '#e8eaed',
            'is_archived' => true,
            'archived_at' => now()->subWeeks(2),
            'labels' => ['archived', 'old projects'],
        ]);

        Note::create([
            'user_id' => $user->id,
            'title' => 'Last Year\'s Resolutions',
            'content' => "• Exercise 3x per week ✓\n• Read 12 books ✓\n• Learn Spanish ⏳\n• Save $5000 ✓\n• Travel to 2 new places ✓",
            'color' => '#ccff90',
            'is_archived' => true,
            'archived_at' => now()->subMonths(3),
            'labels' => ['goals', 'resolutions'],
        ]);
    }

    /**
     * Create some notes in trash
     */
    private function createTrashedNotes(User $user): void
    {
        Note::create([
            'user_id' => $user->id,
            'title' => 'Deleted Draft',
            'content' => 'This was just a test note that got deleted.',
            'color' => '#ffffff',
            'deleted_at' => now()->subDays(3),
            'labels' => ['draft'],
        ]);

        Note::create([
            'user_id' => $user->id,
            'title' => null,
            'content' => 'Random thoughts that are no longer needed...',
            'color' => '#e8eaed',
            'deleted_at' => now()->subDays(5),
        ]);
    }
}