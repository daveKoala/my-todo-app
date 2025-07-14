<?php

namespace DaveKoala\RoutesExplorer;

use Illuminate\Support\ServiceProvider;

class RoutesExplorerServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register any bindings here if needed
    }

    public function boot()
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        
        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'routes-explorer');
        
        // Publish views (optional, for when this becomes an external package)
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/routes-explorer'),
        ], 'views');
    }
}