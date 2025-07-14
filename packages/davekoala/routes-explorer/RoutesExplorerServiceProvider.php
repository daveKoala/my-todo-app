<?php

namespace DaveKoala\RoutesExplorer;

use Illuminate\Support\ServiceProvider;
use DaveKoala\RoutesExplorer\Http\Controllers\RoutesExplorerController;

class RoutesExplorerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        
        // Load views
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'routes-explorer');
        
        // Publish assets if needed
        $this->publishes([
            __DIR__ . '/resources/views' => resource_path('views/vendor/routes-explorer'),
        ], 'views');
    }

    public function register()
    {
        // Register the controller
        $this->app->bind(RoutesExplorerController::class);
    }
}