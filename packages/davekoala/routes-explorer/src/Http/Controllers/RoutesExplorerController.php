<?php

namespace DaveKoala\RoutesExplorer\Http\Controllers;

use DaveKoala\RoutesExplorer\Explorer\ClassAnalysisEngine;
use DaveKoala\RoutesExplorer\Explorer\StringHelpers;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Controller;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

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
            $verbString = $request->get('verb');
            $strHelper = new StringHelpers();
            $verb = $strHelper->getVerb($verbString);
            $routeUri = $request->get('route');

            // Find the route
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
            $analysisEngine = new ClassAnalysisEngine();

            // Enhanced mock command with better error handling
            $mockCommand = new class extends Command {
                protected $signature = 'mock:command';
                protected $description = 'Mock command';

                protected array $logs = [];

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
            };

            // Analyze the route with proper error handling
            $relationships = [];

            try {
                $relationships = $analysisEngine->exploreRoute($route, $mockCommand);
            } catch (\Throwable $e) {
                // Log the error but don't fail completely
                $mockCommand->error("Analysis error: " . $e->getMessage());
                $mockCommand->error("File: " . $e->getFile() . " Line: " . $e->getLine());
            }

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
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : 'Enable debug mode to see trace'
            ], 500);
        }
    }

}
