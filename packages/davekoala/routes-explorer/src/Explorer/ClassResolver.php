<?php

namespace DaveKoala\RoutesExplorer\Explorer;

use ReflectionClass;
use ReflectionException;

/**
 * Laravel-native class resolution without autoloader hacks
 */
class ClassResolver
{
    private array $namespaces;
    private array $skipNamespaces;

    public function __construct()
    {
        // Ensure application autoloader is available
        $this->ensureApplicationAutoloader();

        // Provide comprehensive fallbacks if config fails to load
        $this->namespaces = config('routes-explorer.namespaces', [
            'app' => 'App\\',
            'models' => 'App\\Models\\',
            'controllers' => 'App\\Http\\Controllers\\',
            'middleware' => 'App\\Http\\Middleware\\',
            'jobs' => 'App\\Jobs\\',
            'events' => 'App\\Events\\',
            'notifications' => 'App\\Notifications\\',
            'listeners' => 'App\\Listeners\\',
            'policies' => 'App\\Policies\\',
            'rules' => 'App\\Rules\\',
            'providers' => 'App\\Providers\\',
        ]);

        $this->skipNamespaces = config('routes-explorer.skip_namespaces', [
            'Illuminate\\',
            'Symfony\\',
            'Psr\\',
            'Carbon\\',
            'Monolog\\',
            'Laravel\\',
        ]);
    }

    /**
     * Check if a class exists and can be loaded using Laravel's native mechanisms
     */
    public function classExists(string $className): bool
    {
        // First try the standard PHP class_exists which works with Composer autoloader
        if (class_exists($className)) {
            return true;
        }

        // Fallback: try ReflectionClass
        try {
            new ReflectionClass($className);
            return true;
        } catch (ReflectionException $e) {
            return false;
        }
    }

    /**
     * Try to resolve a partial class name to a full namespaced class
     */
    public function resolveClassName(string $partialName): ?string
    {
        // If it's already a full class name and exists, return it
        if ($this->classExists($partialName)) {
            return $partialName;
        }

        // Try each configured namespace
        foreach ($this->namespaces as $type => $namespace) {
            $fullClassName = $namespace . $partialName;
            if ($this->classExists($fullClassName)) {
                return $fullClassName;
            }
        }

        return null;
    }

    /**
     * Check if we should skip analyzing this class (framework classes, etc.)
     */
    public function shouldSkipClass(string $className): bool
    {
        foreach ($this->skipNamespaces as $skipNamespace) {
            if (str_starts_with($className, $skipNamespace)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get reflection for a class if it exists
     */
    public function getReflection(string $className): ?ReflectionClass
    {
        // First check if class exists using standard PHP method
        if (!$this->classExists($className)) {
            return null;
        }

        try {
            return new ReflectionClass($className);
        } catch (ReflectionException $e) {
            return null;
        }
    }

    /**
     * Build full class name from pattern match and verify it exists
     */
    public function buildAndVerifyClass(string $baseName, string $type = 'app'): ?array
    {
        $namespace = $this->namespaces[$type] ?? $this->namespaces['app'];
        $fullClassName = $namespace . $baseName;

        if ($this->classExists($fullClassName)) {
            return [
                'class' => $fullClassName,
                'base_name' => $baseName,
                'namespace' => $namespace,
                'type' => $type
            ];
        }

        return null;
    }

    /**
     * Get available namespaces for debugging
     */
    public function getNamespaces(): array
    {
        return $this->namespaces;
    }

    /**
     * Ensure application classes can be autoloaded from package context
     * This is a clean approach that works with Laravel's structure
     */
    private function ensureApplicationAutoloader(): void
    {
        // Method 1: Ensure the main autoloader is loaded
        $autoloaderPath = base_path('vendor/autoload.php');
        if (file_exists($autoloaderPath)) {
            require_once $autoloaderPath;
        }

        // Method 2: Register a PSR-4 autoloader for App namespace specifically
        // This is cleaner than manual file loading but ensures App classes work
        if (!class_exists('App\\Http\\Controllers\\Controller', false)) {
            spl_autoload_register(function ($class) {
                // Only handle App\ namespace
                if (strncmp($class, 'App\\', 4) === 0) {
                    $file = app_path() . '/' . str_replace(['App\\', '\\'], ['', '/'], $class) . '.php';
                    if (file_exists($file)) {
                        require_once $file;
                        return true;
                    }
                }
                return false;
            });
        }
    }
}