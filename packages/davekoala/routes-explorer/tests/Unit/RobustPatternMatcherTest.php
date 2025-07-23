<?php

namespace DaveKoala\RoutesExplorer\Tests\Unit;

use DaveKoala\RoutesExplorer\Explorer\RobustPatternMatcher;
use DaveKoala\RoutesExplorer\Tests\TestCase;

class RobustPatternMatcherTest extends TestCase
{
    /** @test */
    public function it_handles_flexible_whitespace_in_auth_calls()
    {
        $sources = [
            'Auth::user()',           // Standard
            'Auth :: user()',         // Extra spaces
            'Auth::user( )',          // Space in parens
            'auth()->user()',         // Helper function
            '$auth::user()',          // Variable
        ];

        foreach ($sources as $source) {
            $matches = RobustPatternMatcher::matchAuthUser($source);
            $this->assertNotEmpty($matches, "Failed to match: {$source}");
            $this->assertEquals('auth_user', $matches[0]['pattern_type']);
        }
    }

    /** @test */
    public function it_handles_flexible_guard_expressions()
    {
        $sources = [
            "Auth::guard('admin')->user()",           // String literal
            'Auth::guard("api")->user()',             // Double quotes  
            'Auth::guard($guard)->user()',            // Variable
            'Auth::guard(config("auth.guard"))->user()', // Config call
        ];

        foreach ($sources as $source) {
            $matches = RobustPatternMatcher::matchAuthGuard($source);
            $this->assertNotEmpty($matches, "Failed to match: {$source}");
            $this->assertEquals('auth_guard', $matches[0]['pattern_type']);
        }
    }

    /** @test */
    public function it_handles_various_job_dispatch_patterns()
    {
        $sources = [
            'dispatch(new SendEmail())',              // Standard
            'dispatch(new SendEmail($user))',         // With params
            'SendEmail::dispatch()',                  // Static call
            'dispatch(SendEmail::class)',             // Class constant
        ];

        foreach ($sources as $source) {
            $matches = RobustPatternMatcher::matchJobDispatch($source);
            $this->assertNotEmpty($matches, "Failed to match: {$source}");
            $this->assertEquals('job_dispatch', $matches[0]['pattern_type']);
            $this->assertEquals('SendEmail', $matches[0]['class_name']);
        }
    }

    /** @test */
    public function it_detects_model_static_calls_with_various_methods()
    {
        $sources = [
            'User::find(1)',
            'Post::create($data)',
            'Order::where("status", "active")',
            'User :: first()',                        // Extra spaces
        ];

        foreach ($sources as $source) {
            $matches = RobustPatternMatcher::matchModelStatic($source);
            $this->assertNotEmpty($matches, "Failed to match: {$source}");
            $this->assertEquals('model_static', $matches[0]['pattern_type']);
        }
    }

    /** @test */
    public function it_removes_duplicate_matches()
    {
        $matches = [
            ['offset' => 10, 'class_name' => 'User', 'pattern_type' => 'model'],
            ['offset' => 10, 'class_name' => 'User', 'pattern_type' => 'model'], // Duplicate
            ['offset' => 50, 'class_name' => 'Post', 'pattern_type' => 'model'],
        ];

        $unique = RobustPatternMatcher::removeDuplicateMatches($matches);
        
        $this->assertCount(2, $unique);
        $this->assertEquals('User', $unique[0]['class_name']);
        $this->assertEquals('Post', $unique[1]['class_name']);
    }
}