<?php

namespace DaveKoala\RoutesExplorer\Explorer;

use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Routing\Route;

/**
 * Web Route Discovery Helper
 * 
 * Simplified version for web interface - no Command dependency needed.
 * Just finds routes and returns them or null.
 */
class WebRouteDiscovery
{
    /**
     * Find a route by identifier (name, URI, or URI with method)
     * 
     * Supports formats like:
     * - "notes.show" (by name)
     * - "notes/{note}" (by URI)
     * - "GET notes/{note}" (by method and URI)
     */
    public function findRoute(string $identifier): ?Route
    {
        $routes = RouteFacade::getRoutes();
        
        // Clean up the input
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
            foreach ($routes as $route) {
                if ($route->uri() === $parsed['uri']) {
                    return $route; // Return first match for web interface
                }
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
     * Clean up route input
     */
    private function cleanRouteInput(string $input): string
    {
        $cleaned = trim($input);
        return preg_replace('/\s+/', ' ', $cleaned);
    }
    
    /**
     * Parse method and URI from input string
     */
    private function parseMethodAndUri(string $input): array
    {
        $result = ['method' => null, 'uri' => null];
        
        if (preg_match('/^(GET|POST|PUT|PATCH|DELETE|HEAD|OPTIONS)(\|[A-Z]+)*\s+(.+)$/i', $input, $matches)) {
            $result['method'] = strtoupper($matches[1]);
            $result['uri'] = ltrim($matches[3], '/');
        } else {
            $result['uri'] = ltrim($input, '/');
        }
        
        return $result;
    }
}