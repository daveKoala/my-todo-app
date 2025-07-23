<?php

namespace DaveKoala\RoutesExplorer\Tests\Feature;

use DaveKoala\RoutesExplorer\Explorer\ClassAnalysisEngine;
use DaveKoala\RoutesExplorer\Tests\TestCase;
use Illuminate\Console\Command;
use Illuminate\Routing\Route;

class BasicIntegrationTest extends TestCase
{
    /** @test */
    public function it_can_analyze_a_basic_route()
    {
        config(['app.env' => 'testing']);
        config(['app.debug' => true]);

        $engine = new ClassAnalysisEngine();
        $mockCommand = new MockCommand();
        
        // Create a simple route
        $route = new Route(['GET'], '/test', ['controller' => 'Illuminate\\Routing\\Controller@index']);
        $route->bind(request());

        $relationships = $engine->exploreRoute($route, $mockCommand);

        $this->assertIsArray($relationships);
        
        $logs = $mockCommand->getLogs();
        $this->assertNotEmpty($logs);
        
        // Check that analysis started
        $this->assertStringContainsString('Exploring route chain', $logs[0]['message']);
    }

    /** @test */
    public function it_enforces_security_requirements()
    {
        // Set up production environment (should be blocked)
        $this->app['env'] = 'production';
        config(['routes-explorer.security.allowed_environments' => ['testing']]);

        $engine = new ClassAnalysisEngine();
        $mockCommand = new MockCommand();
        
        $route = new Route(['GET'], '/test', ['controller' => 'TestController@index']);
        $route->bind(request());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Routes Explorer analysis is disabled in \'production\' environment');

        $engine->exploreRoute($route, $mockCommand);
    }
}

/**
 * Simple mock command for testing
 */
class MockCommand extends Command
{
    protected $signature = 'mock:command';
    protected $description = 'Mock command for testing';
    private array $logs = [];

    public function info($string, $verbosity = null): void
    {
        $this->logs[] = ['type' => 'info', 'message' => $string];
    }

    public function line($string = '', $style = null, $verbosity = null): void
    {
        $this->logs[] = ['type' => 'line', 'message' => $string];
    }

    public function warn($string, $verbosity = null): void
    {
        $this->logs[] = ['type' => 'warn', 'message' => $string];
    }

    public function error($string, $verbosity = null): void
    {
        $this->logs[] = ['type' => 'error', 'message' => $string];
    }

    public function getLogs(): array
    {
        return $this->logs;
    }
}