<?php

namespace DaveKoala\RoutesExplorer\Tests;

use DaveKoala\RoutesExplorer\RoutesExplorerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up test environment
        config(['app.debug' => true]);
        config(['app.env' => 'testing']);
    }

    protected function getPackageProviders($app)
    {
        return [
            RoutesExplorerServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        // Define environment setup
        $app['config']->set('routes-explorer.security.allowed_environments', ['testing', 'local']);
        $app['config']->set('routes-explorer.security.require_debug', true);
        $app['config']->set('routes-explorer.max_depth', 3);
    }
}