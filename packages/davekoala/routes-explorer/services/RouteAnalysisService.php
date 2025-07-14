<?php

namespace DaveKoala\RoutesExplorer\Services;

use Illuminate\Support\Facades\Route;
use DaveKoala\RoutesExplorer\Console\Commands\RouteDiscovery;
use DaveKoala\RoutesExplorer\Console\Commands\ClassAnalysisEngine;

class RouteAnalysisService
{
    private RouteDiscovery $routeDiscovery;
    private ClassAnalysisEngine $analysisEngine;

    public function __construct()
    {
        $this->routeDiscovery = new RouteDiscovery();
    }

    public function analyzeRoute(string $routeIdentifier, int $depth = 3): array
    {
        $route = $this->routeDiscovery->findRoute($routeIdentifier);
        
        if (!$route) {
            throw new \Exception("Route '{$routeIdentifier}' not found");
        }

        $this->analysisEngine = new ClassAnalysisEngine($depth);
        
        // Create a mock command object for the analysis engine
        $mockCommand = new class {
            public function info($message) { }
            public function line($message) { }
            public function warn($message) { }
            public function error($message) { }
        };

        $relationships = $this->analysisEngine->exploreRoute($route, $mockCommand);

        return [
            'route_info' => [
                'name' => $route->getName(),
                'uri' => $route->uri(),
                'methods' => $route->methods(),
                'action' => $route->getActionName(),
                'middleware' => $route->middleware(),
            ],
            'relationships' => $relationships,
            'depth' => $depth
        ];
    }

    public function getAllRoutes(): array
    {
        $routes = [];
        
        foreach (Route::getRoutes() as $route) {
            $routes[] = [
                'name' => $route->getName(),
                'uri' => $route->uri(),
                'methods' => $route->methods(),
                'action' => $route->getActionName(),
            ];
        }
        
        return $routes;
    }
}