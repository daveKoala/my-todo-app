<?php

namespace DaveKoala\RoutesExplorer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;
use DaveKoala\RoutesExplorer\Services\RouteAnalysisService;

class RoutesExplorerController extends Controller
{
    private RouteAnalysisService $analysisService;

    public function __construct(RouteAnalysisService $analysisService)
    {
        $this->analysisService = $analysisService;
    }

    public function index()
    {
        $routes = $this->getAllRoutes();
        
        return view('routes-explorer::index', [
            'routes' => $routes
        ]);
    }

    public function explore(Request $request, $routeIdentifier)
    {
        try {
            $analysis = $this->analysisService->analyzeRoute($routeIdentifier);
            
            return view('routes-explorer::explore', [
                'routeIdentifier' => $routeIdentifier,
                'analysis' => $analysis
            ]);
        } catch (\Exception $e) {
            return redirect()->route('routes-explorer.index')
                ->with('error', 'Route not found: ' . $routeIdentifier);
        }
    }

    public function apiExplore(Request $request)
    {
        $routeIdentifier = $request->input('route');
        $depth = $request->input('depth', 3);
        
        try {
            $analysis = $this->analysisService->analyzeRoute($routeIdentifier, $depth);
            
            return response()->json([
                'success' => true,
                'data' => $analysis
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 404);
        }
    }

    private function getAllRoutes(): array
    {
        $routes = [];
        
        foreach (Route::getRoutes() as $route) {
            $routes[] = [
                'name' => $route->getName(),
                'uri' => $route->uri(),
                'methods' => $route->methods(),
                'action' => $route->getActionName(),
                'middleware' => $route->middleware(),
            ];
        }
        
        return $routes;
    }
}