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

            // CRITICAL FIX: Ensure all classes can be autoloaded properly
            $this->ensureAutoloaderAccess();

            // Use your ClassAnalysisEngine to analyze the route
            $analysisEngine = new ClassAnalysisEngine(5);

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

    /**
     * Ensure the package has access to the main application's autoloader
     * 
     * This is the critical fix - we need to make sure all app classes
     * can be found and loaded properly from within the package context.
     */
    private function ensureAutoloaderAccess(): void
    {
        // Force Composer to regenerate autoloader for all App classes
        // This ensures the package can see all application classes

        // Method 1: Explicitly load the app's composer autoloader
        $appAutoloader = base_path('vendor/autoload.php');
        if (file_exists($appAutoloader)) {
            require_once $appAutoloader;
        }

        // Method 2: Register the App namespace explicitly
        $appPath = app_path();
        if (function_exists('spl_autoload_register')) {
            spl_autoload_register(function ($class) use ($appPath) {
                // Only handle App\ namespace classes
                if (strpos($class, 'App\\') === 0) {
                    $relativePath = str_replace('App\\', '', $class);
                    $file = $appPath . '/' . str_replace('\\', '/', $relativePath) . '.php';

                    if (file_exists($file)) {
                        require_once $file;
                        return true;
                    }
                }
                return false;
            });
        }

        // Method 3: Pre-load common Laravel classes that often cause issues
        $commonClasses = [
            'App\\Http\\Controllers\\Controller',
            'App\\Models\\User',
        ];

        foreach ($commonClasses as $class) {
            if (!class_exists($class, false)) { // Check if already loaded
                $file = $this->classToFile($class);
                if ($file && file_exists($file)) {
                    try {
                        require_once $file;
                    } catch (\Throwable $e) {
                        // Ignore errors, just continue
                    }
                }
            }
        }
    }

    /**
     * Convert class name to file path
     */
    private function classToFile(string $class): ?string
    {
        if (strpos($class, 'App\\') === 0) {
            $relativePath = str_replace('App\\', '', $class);
            return app_path() . '/' . str_replace('\\', '/', $relativePath) . '.php';
        }
        return null;
    }
}
