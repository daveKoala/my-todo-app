<?php

namespace App\Console\Commands\ExploreRouteCommand;

use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Console\Command;
use Illuminate\Routing\Route;

/**
 * Route Discovery Helper
 * 
 * Handles finding Laravel routes and displaying route information.
 * Enhanced to handle multiple HTTP methods for the same URI pattern.
 */
class RouteDiscovery
{
    /**
     * Find a route by identifier (name, URI, or URI with method)
     * 
     * Supports formats like:
     * - "notes.show" (by name)
     * - "notes/{note}" (by URI - will show options if multiple methods)
     * - "GET notes/{note}" (by method and URI)
     * - " DELETE          notes/{note}/force" (copy-pasted from route:list)
     */
    public function findRoute(string $identifier): ?Route
    {
        $routes = RouteFacade::getRoutes();
        
        // Clean up the input - handle copy-paste from route:list
        $cleanIdentifier = $this->cleanRouteInput($identifier);
        
        // Try to find by name first (exact match)
        $route = $routes->getByName($cleanIdentifier);
        if ($route) return $route;
        
        // Parse method and URI from the cleaned input
        $parsed = $this->parseMethodAndUri($cleanIdentifier);
        
        if ($parsed['method'] && $parsed['uri']) {
            // We have both method and URI - find exact match
            foreach ($routes as $route) {
                if ($route->uri() === $parsed['uri'] && in_array($parsed['method'], $route->methods())) {
                    return $route;
                }
            }
        }
        
        // If we only have URI (no method specified), try to find matches
        if ($parsed['uri'] && !$parsed['method']) {
            $matchingRoutes = [];
            
            foreach ($routes as $route) {
                if ($route->uri() === $parsed['uri']) {
                    $matchingRoutes[] = $route;
                }
            }
            
            // If we found exactly one route, return it
            if (count($matchingRoutes) === 1) {
                return $matchingRoutes[0];
            }
            
            // If we found multiple routes with the same URI, we need user to be more specific
            if (count($matchingRoutes) > 1) {
                $this->multipleMatches = $matchingRoutes;
                return null;
            }
        }
        
        // Try partial match on the cleaned URI
        $searchUri = $parsed['uri'] ?: $cleanIdentifier;
        foreach ($routes as $route) {
            if (str_contains($route->uri(), $searchUri)) {
                return $route;
            }
        }
        
        return null;
    }
    
    /**
     * Clean up route input - handle copy-paste from route:list output
     * 
     * Handles formats like:
     * - " DELETE          notes/{note}/force"
     * - "GET|HEAD         api/users"  
     * - "  POST    users/{user}/posts  "
     */
    private function cleanRouteInput(string $input): string
    {
        // Trim whitespace
        $cleaned = trim($input);
        
        // Remove extra whitespace between words
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        
        return $cleaned;
    }
    
    /**
     * Parse method and URI from input string
     * 
     * Returns array with 'method' and 'uri' keys
     */
    private function parseMethodAndUri(string $input): array
    {
        $result = ['method' => null, 'uri' => null];
        
        // Pattern to match HTTP methods at the start, handling multiple methods like "GET|HEAD"
        if (preg_match('/^(GET|POST|PUT|PATCH|DELETE|HEAD|OPTIONS)(\|[A-Z]+)*\s+(.+)$/i', $input, $matches)) {
            // Extract the first method (most specific)
            $methodPart = $matches[1];
            $result['method'] = strtoupper($methodPart);
            $result['uri'] = ltrim($matches[3], '/');
        } else {
            // No method found, treat entire input as URI
            $result['uri'] = ltrim($input, '/');
        }
        
        return $result;
    }
    
    private array $multipleMatches = [];
    
    /**
     * Display route information in a nice table format
     */
    public function displayRouteInfo(Route $route, Command $command): void
    {
        $command->line('');
        $command->info('📍 Route Information:');
        $command->table(['Property', 'Value'], [
            ['URI', $route->uri()],
            ['Name', $route->getName() ?: 'N/A'],
            ['Methods', implode('|', $route->methods())],
            ['Action', $route->getActionName()],
            ['Middleware', implode(', ', $route->middleware()) ?: 'None'],
        ]);
        $command->line('');
    }
    
    /**
     * Suggest similar routes when the requested route is not found
     */
    public function suggestSimilarRoutes(string $identifier, Command $command): void
    {
        $command->line('');
        
        // If we have multiple exact matches, show them specifically
        if (!empty($this->multipleMatches)) {
            $command->error("Multiple routes found for URI pattern:");
            $command->line('');
            $command->info('🎯 Please specify the HTTP method:');
            
            $suggestions = [];
            foreach ($this->multipleMatches as $route) {
                $methods = implode('|', $route->methods());
                $suggestions[] = [
                    $route->getName() ?: 'N/A',
                    $route->uri(),
                    $methods,
                    "Try: {$route->methods()[0]} {$route->uri()}"
                ];
            }
            
            $command->table(['Name', 'URI', 'Methods', 'Example'], $suggestions);
            $command->line('');
            $command->info('💡 Copy-paste friendly examples:');
            foreach ($this->multipleMatches as $route) {
                $method = $route->methods()[0]; // Use first method as example
                $command->line("  php artisan explore:route \"{$method} {$route->uri()}\"");
            }
            return;
        }
        
        // Original suggestion logic for general searches
        $command->info('💡 Did you mean one of these routes?');
        
        $routes = RouteFacade::getRoutes();
        $suggestions = [];
        $parsed = $this->parseMethodAndUri($this->cleanRouteInput($identifier));
        $searchTerm = $parsed['uri'] ?: $identifier;
        
        foreach ($routes as $route) {
            if (str_contains($route->uri(), $searchTerm) || 
                str_contains($route->getName() ?? '', $searchTerm)) {
                $suggestions[] = [
                    $route->getName() ?: 'N/A',
                    $route->uri(),
                    implode('|', $route->methods())
                ];
            }
        }
        
        if (!empty($suggestions)) {
            $command->table(['Name', 'URI', 'Methods'], array_slice($suggestions, 0, 10));
            $command->line('');
            $command->info('💡 Tips:');
            $command->line('  • Copy-paste directly from "php artisan route:list" output');
            $command->line('  • Example: php artisan explore:route "DELETE notes/{note}/force"');
            $command->line('  • Whitespace and multiple methods (GET|HEAD) are handled automatically');
        } else {
            $command->line('No similar routes found. Try: php artisan route:list');
        }
    }
}