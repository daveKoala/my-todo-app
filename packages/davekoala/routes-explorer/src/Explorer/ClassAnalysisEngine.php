<?php

namespace DaveKoala\RoutesExplorer\Explorer;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use ReflectionMethod;
use ReflectionClass;

/**
 * Enhanced Class Analysis Engine with debugging
 */
class ClassAnalysisEngine
{
    private TypeDetectors $typeDetectors;
    private array $relationships = [];
    private array $analyzed = [];
    private int $maxDepth;

    public function __construct(int $maxDepth = 3)
    {
        $this->maxDepth = $maxDepth;
        $this->typeDetectors = new TypeDetectors();
    }

    /**
     * Main entry point - explore a Laravel route and all its dependencies
     */
    public function exploreRoute(Route $route, Command $command): array
    {
        $command->info('🔗 Exploring route chain...');
        $command->line('');

        // Add some debugging about the environment
        $command->line("🔍 Debug: App path: " . app_path());
        $command->line("🔍 Debug: Base path: " . base_path());

        // First, explore middleware
        $this->exploreRouteMiddleware($route, 0, $command);
        $command->line('');

        $action = $route->getAction();

        if (isset($action['controller'])) {
            [$controllerClass, $method] = explode('@', $action['controller']);

            $command->line("🎯 Starting from: {$controllerClass}@{$method}");
            $command->line('');

            // Add debugging for class discovery
            $command->line("🔍 Debug: Checking if class exists: {$controllerClass}");
            $command->line("🔍 Debug: class_exists() result: " . (class_exists($controllerClass) ? 'true' : 'false'));

            // Try to get more info about why it might not exist
            if (!class_exists($controllerClass)) {
                // Check if file exists
                $expectedPath = app_path('Http/Controllers/' . class_basename($controllerClass) . '.php');
                $command->line("🔍 Debug: Expected file path: {$expectedPath}");
                $command->line("🔍 Debug: File exists: " . (file_exists($expectedPath) ? 'true' : 'false'));

                // Try alternative paths
                $altPath = base_path('app/Http/Controllers/' . class_basename($controllerClass) . '.php');
                $command->line("🔍 Debug: Alternative path: {$altPath}");
                $command->line("🔍 Debug: Alt file exists: " . (file_exists($altPath) ? 'true' : 'false'));

                // List actual controller files
                $controllerDir = app_path('Http/Controllers');
                if (is_dir($controllerDir)) {
                    $files = array_slice(scandir($controllerDir), 2, 5); // Skip . and .., take first 5
                    $command->line("🔍 Debug: Controllers directory contents (first 5): " . implode(', ', $files));
                } else {
                    $command->line("🔍 Debug: Controllers directory not found at: {$controllerDir}");
                }

                // Try to manually include the file and check again
                if (file_exists($expectedPath)) {
                    $command->line("🔍 Debug: Attempting to manually require file...");
                    try {
                        require_once $expectedPath;
                        $command->line("🔍 Debug: After require - class_exists(): " . (class_exists($controllerClass) ? 'true' : 'false'));
                    } catch (\Exception $e) {
                        $command->line("🔍 Debug: Error requiring file: " . $e->getMessage());
                    }
                }
            }

            // Explore the controller class
            $this->exploreClass($controllerClass, 0, 'Controller', $command);

            // Explore the specific method with enhanced analysis
            $this->exploreMethodEnhanced($controllerClass, $method, 1, $command);
        } elseif (isset($action['uses']) && is_callable($action['uses'])) {
            $command->line("🎯 Route uses a Closure - exploring dependencies");
        } else {
            $command->warn("⚠️  Could not determine route action");
        }

        return $this->relationships;
    }

    // ... rest of the methods remain the same ...

    private function exploreClass(string $className, int $depth, string $context, Command $command): void
    {
        // Prevent infinite loops and respect max depth
        if (isset($this->analyzed[$className]) || $depth > $this->maxDepth) {
            if ($depth > $this->maxDepth) {
                $command->line(str_repeat('  ', $depth) . "⚠️  Max depth reached for {$className}");
            }
            return;
        }

        $this->analyzed[$className] = true;

        if (!class_exists($className)) {
            $command->line(str_repeat('  ', $depth) . "❌ {$className} not found");
            return;
        }

        $reflection = new ReflectionClass($className);
        $indent = str_repeat('  ', $depth);

        $command->line("{$indent}📦 {$this->typeDetectors->getClassEmoji($reflection)} {$className} {$context}");

        // Store relationship data
        $this->relationships[$className] = [
            'extends' => ($parent = $reflection->getParentClass()) ? $parent->getName() : null,
            'type' => $this->typeDetectors->getClassType($reflection),
            'implements' => array_keys($reflection->getInterfaces()),
            'traits' => array_keys($reflection->getTraits()),
            'file' => $reflection->getFileName(),
            'context' => $context,
            'name' => $className,
            'dependencies' => [],
            'depth' => $depth,
        ];

        // Explore relationships
        if ($parent = $reflection->getParentClass()) {
            $command->line("{$indent}  ↗️  Extends: {$parent->getName()}");
            $this->exploreClass($parent->getName(), $depth + 1, 'Parent Class', $command);
        }

        foreach ($reflection->getInterfaces() as $interface) {
            $command->line("{$indent}  🔌 Implements: {$interface->getName()}");
            $this->exploreClass($interface->getName(), $depth + 1, 'Interface', $command);
        }

        foreach ($reflection->getTraits() as $trait) {
            $command->line("{$indent}  🧩 Uses Trait: {$trait->getName()}");
            $this->exploreClass($trait->getName(), $depth + 1, 'Trait', $command);
        }

        if ($constructor = $reflection->getConstructor()) {
            $this->exploreMethodDependencies($constructor, $className, $depth + 1, 'Constructor', $command);
        }
    }

    // Add placeholder methods to make this compile
    private function exploreRouteMiddleware(Route $route, int $depth, Command $command): void
    {
        $middleware = $route->middleware();
        if (empty($middleware)) return;

        $indent = str_repeat('  ', $depth);
        $command->line("{$indent}🛡️  Middleware:");

        foreach ($middleware as $mw) {
            if (in_array($mw, ['web', 'api'])) {
                $command->line("{$indent}  ├─ {$mw} (group)");
                continue;
            }
            $command->line("{$indent}  ├─ {$mw}");
        }
    }

    private function exploreMethodEnhanced(string $className, string $methodName, int $depth, Command $command): void
    {
        if (!class_exists($className)) return;

        $reflection = new ReflectionClass($className);

        if (!$reflection->hasMethod($methodName)) {
            $command->line(str_repeat('  ', $depth) . "❌ Method {$methodName} not found in {$className}");
            return;
        }

        $method = $reflection->getMethod($methodName);
        $indent = str_repeat('  ', $depth);

        $command->line("{$indent}🔧 Method: {$methodName}()");

        $this->exploreMethodDependencies($method, $className, $depth + 1, "Method: {$methodName}", $command);
    }

    private function exploreMethodDependencies(ReflectionMethod $method, string $className, int $depth, string $context, Command $command): void
    {
        $parameters = $method->getParameters();
        $indent = str_repeat('  ', $depth);

        foreach ($parameters as $param) {
            $type = $param->getType();

            if (!$type || $type->isBuiltin()) continue;

            $typeName = $type->getName();

            if ($this->typeDetectors->shouldSkipFrameworkClass($typeName)) {
                $command->line("{$indent}📋 {$param->getName()}: {$typeName} (Framework)");
                continue;
            }

            $command->line("{$indent}💉 Injected: {$param->getName()}: {$typeName}");

            if (!isset($this->relationships[$className])) {
                $this->relationships[$className] = ['dependencies' => []];
            }

            $this->relationships[$className]['dependencies'][] = [
                'class' => $typeName,
                'parameter' => $param->getName(),
                'context' => $context
            ];

            $this->exploreClass($typeName, $depth + 1, 'Dependency', $command);

            if ($this->typeDetectors->isEloquentModel($typeName)) {
                $this->exploreModelRelationships($typeName, $depth + 1, $command);
            }
        }
    }

    private function exploreModelRelationships(string $modelClass, int $depth, Command $command): void
    {
        // Simplified implementation for debugging
        $command->line(str_repeat('  ', $depth) . "🗄️  Model relationships exploration (simplified for debugging)");
    }
}
