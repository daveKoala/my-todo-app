<?php

namespace App\Console\Commands\ExploreRouteCommand;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;

/**
 * Laravel Route Explorer Command
 * 
 * Enhanced to handle multiple HTTP methods for the same URI pattern:
 * 
 * php artisan explore:route notes.show
 * php artisan explore:route "notes/{note}"
 * php artisan explore:route "GET notes/{note}"
 * php artisan explore:route "PATCH notes/{note}"
 * 
 * This expands the Command class and was created with Artisan command:
 * php artisan make:command ExploreRouteCommand
 * 
 * This the main orchestration class - delegates to specialized helper classes
 * for route discovery, class analysis, and output formatting.
 * 
 * All classes are in the same namespace for easy packaging.
 */
class ExploreRouteCommand extends Command
{
    /**
     * The $signature Property:
     * Updated description to clarify copy-paste support
     */
    protected $signature = 'explore:route {route : Route name, URI, or copy-paste from route:list (handles whitespace)} {--depth=3 : Maximum exploration depth} {--format=table : Output format (table|json|tree)}';
    
    protected $description = 'Explore a Laravel route and show all related classes, dependencies and relationships. Copy-paste directly from route:list output!';
    
    private array $relationships = [];
    private array $analyzed = [];
    private int $maxDepth;
    
    public function handle()
    {
        // Gets whatever is typed after the command name
        $routeIdentifier = $this->argument('route');
        $this->maxDepth = (int) $this->option('depth');
        $format = $this->option('format');
        
        $this->info("🔍 Exploring Laravel route: {$routeIdentifier}");
        $this->line(str_repeat('=', 60));
        
        try {
            // Use RouteDiscovery helper to find the route
            $routeDiscovery = new RouteDiscovery();
            $route = $routeDiscovery->findRoute($routeIdentifier);
            
            if (!$route) {
                $this->error("Route '{$routeIdentifier}' not found!");
                // Show list of routes or specific guidance for multiple matches
                $routeDiscovery->suggestSimilarRoutes($routeIdentifier, $this);
                return Command::FAILURE;
            }
            
            // Display route info using RouteDiscovery helper
            $routeDiscovery->displayRouteInfo($route, $this);
            
            // Initialize analysis state
            $this->analyzed = [];
            $this->relationships = [];
            
            // Use ClassAnalysisEngine to perform the exploration
            $analysisEngine = new ClassAnalysisEngine($this->maxDepth);
            $this->relationships = $analysisEngine->exploreRoute($route, $this);
            
            // Use OutputFormatters to display results
            $formatter = new OutputFormatters();
            $formatter->output($this->relationships, $format, $this);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Getter methods for helper classes to access command state
     */
    public function getAnalyzed(): array
    {
        return $this->analyzed;
    }
    
    public function setAnalyzed(array $analyzed): void
    {
        $this->analyzed = $analyzed;
    }
    
    public function getMaxDepth(): int
    {
        return $this->maxDepth;
    }
}