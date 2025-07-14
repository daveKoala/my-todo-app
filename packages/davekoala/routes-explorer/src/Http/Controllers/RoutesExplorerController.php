<?php

namespace DaveKoala\RoutesExplorer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;

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
         // Get the parameters from the request
        $verb = $request->get('verb');
        $route = $request->get('route');
        
        // Return a JSON response
        return response()->json([
            'message' => 'Route test initiated',
            'verb' => $verb,
            'route' => $route,
            'timestamp' => now()->toDateTimeString(),
            'status' => 'success'
        ]);
    }
}