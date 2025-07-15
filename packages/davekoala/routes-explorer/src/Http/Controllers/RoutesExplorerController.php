<?php

namespace DaveKoala\RoutesExplorer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;
use DaveKoala\RoutesExplorer\Explorer\ClassAnalysisEngine;
use DaveKoala\RoutesExplorer\Explorer\StringHelpers;
use Illuminate\Console\Command;

class RoutesExplorerController extends Controller
{
    public function index(Request $request)
    {
        $routes = collect(Route::getRoutes())->map(function ($route) {
            return [
                'method' => implode('|', $route->methods()),
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'action' => $route->getActionName(),
                'middleware' => $route->middleware(),
            ];
        });

        // Filter routes if search query is provided
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $routes = $routes->filter(function ($route) use ($search) {
                return str_contains($route['uri'], $search) ||
                       str_contains($route['name'] ?? '', $search) ||
                       str_contains($route['action'], $search);
            });
        }

        return view('routes-explorer::routes-explorer', compact('routes'));
    }

    public function getRoute(Request $request)
    {
        try {
            $verbString = $request->get('verb'); // Can be 'GET|HEAD' or even things like 'PUT|PATCH', so we split it and return the first
            $strHelper = new StringHelpers();
            $verb = $strHelper->getVerb($verbString);

            $routeUri = $request->get('route');
            
            // Simple route finding - just match by URI and method
            $route = null;
            foreach (Route::getRoutes() as $r) {
                if ($r->uri() === $routeUri && in_array($verb, $r->methods())) {
                    $route = $r;
                    break;
                }
            }
            
            if (!$route) {
                return response()->json([
                    'error' => 'Route not found',
                    'message' => "Could not find route: {$verb} {$routeUri}"
                ], 404);
            }
            
            // Use your ClassAnalysisEngine to analyze the route
            $analysisEngine = new ClassAnalysisEngine(3);
            
            // Simple mock command
            $mockCommand = new class extends Command {
                protected $signature = 'mock:command';
                protected $description = 'Mock command';
                
                protected array $logs = [];
                
                public function info($string, $verbosity = null): void {
                    $this->logs[] = ['type' => 'info', 'message' => $string];
                }
                
                public function line($string = '', $style = null, $verbosity = null): void {
                    $this->logs[] = ['type' => 'line', 'message' => $string];
                }
                
                public function warn($string, $verbosity = null): void {
                    $this->logs[] = ['type' => 'warn', 'message' => $string];
                }
                
                public function getLogs(): array {
                    return $this->logs;
                }
            };
            
            // Analyze the route
            $relationships = $analysisEngine->exploreRoute($route, $mockCommand);
            
            return response()->json([
                'success' => true,
                'route' => [
                    'uri' => $route->uri(),
                    'name' => $route->getName(),
                    'methods' => $route->methods(),
                    'action' => $route->getActionName(),
                ],
                'analysis' => [
                    'relationships' => $relationships,
                    'total_classes' => count($relationships),
                    'logs' => $mockCommand->getLogs(),
                ],
                'timestamp' => now()->toDateTimeString(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Analysis failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}