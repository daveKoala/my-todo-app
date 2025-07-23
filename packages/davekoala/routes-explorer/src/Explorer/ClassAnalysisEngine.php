<?php

namespace DaveKoala\RoutesExplorer\Explorer;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use ReflectionMethod;
use ReflectionClass;

use DaveKoala\RoutesExplorer\Explorer\Patterns\SimpleModelReferences;
use DaveKoala\RoutesExplorer\Explorer\Patterns\ExplicitModelStatic;
use DaveKoala\RoutesExplorer\Explorer\Patterns\NotificationSending;
use DaveKoala\RoutesExplorer\Explorer\Patterns\JobDispatching;
use DaveKoala\RoutesExplorer\Explorer\Patterns\EventDispatch;
use DaveKoala\RoutesExplorer\Explorer\Patterns\NewClassName;
use DaveKoala\RoutesExplorer\Explorer\Patterns\AuthUsers;
use DaveKoala\RoutesExplorer\Explorer\ClassResolver;

/**
 * Enhanced Class Analysis Engine
 * 
 * Also includes method body analysis to detect additional dependencies
 * like Auth::user(), static calls, and class instantiations.
 * 
 * Now includes autoloader fixes to work properly in package context.
 */
class ClassAnalysisEngine
{
    private TypeDetectors $typeDetectors;
    private ClassResolver $classResolver;
    private array $relationships = [];
    private array $analyzed = [];
    private int $maxDepth;

    public function __construct(int $maxDepth = null)
    {
        $this->maxDepth = $maxDepth ?? config('routes-explorer.max_depth', 3);
        
        try {
            $this->typeDetectors = new TypeDetectors();
            $this->classResolver = new ClassResolver();
        } catch (\Exception $e) {
            // Fallback in case of initialization issues
            throw new \RuntimeException("Failed to initialize ClassAnalysisEngine: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Main entry point - explore a Laravel route and all its dependencies
     */
    public function exploreRoute(Route $route, Command $command): array
    {
        // Security check - defense in depth
        $this->validateSecurityRequirements();
        
        $command->info('🔗 Exploring route chain...');
        $command->line('');

        // First, explore middleware
        $this->exploreRouteMiddleware($route, 0, $command);
        $command->line('');

        $action = $route->getAction();

        if (isset($action['controller'])) {
            [$controllerClass, $method] = explode('@', $action['controller']);

            $command->line("🎯 Starting from: {$controllerClass}@{$method}");
            $command->line('');

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

    /**
     * Enhanced method exploration that includes method body analysis
     */
    private function exploreMethodEnhanced(string $className, string $methodName, int $depth, Command $command): void
    {
        $reflection = $this->classResolver->getReflection($className);
        if (!$reflection) return;

        if (!$reflection->hasMethod($methodName)) {
            $command->line(str_repeat('  ', $depth) . "❌ Method {$methodName} not found in {$className}");
            return;
        }

        $method = $reflection->getMethod($methodName);
        $indent = str_repeat('  ', $depth);

        $command->line("{$indent}🔧 Method: {$methodName}()");

        // Original parameter-based dependency analysis
        $this->exploreMethodDependencies($method, $className, $depth + 1, "Method: {$methodName}", $command);

        // Method body analysis for additional dependencies
        $this->exploreMethodBodyDependencies($reflection, $methodName, $className, $depth + 1, $command);
    }

    /**
     * Analyze method body for additional dependencies
     */
    private function exploreMethodBodyDependencies(ReflectionClass $reflection, string $methodName, string $className, int $depth, Command $command): void
    {
        $indent = str_repeat('  ', $depth);

        // Get the method source code for analysis
        $method = $reflection->getMethod($methodName);
        $filename = $reflection->getFileName();

        if (!$filename || !file_exists($filename)) {
            return;
        }

        $file = file($filename);
        $startLine = $method->getStartLine() - 1; // Convert to 0-based
        $endLine = $method->getEndLine() - 1;
        $methodSource = implode('', array_slice($file, $startLine, $endLine - $startLine + 1));

        // Detect various dependency patterns in the method body
        $detectedDependencies = $this->analyzeSourceForDependencies($methodSource);

        foreach ($detectedDependencies as $dependency) {
            $command->line("{$indent}🔍 Found: {$dependency['pattern']} → {$dependency['class']}");

            // Store the dependency
            if (!isset($this->relationships[$className]['dependencies'])) {
                $this->relationships[$className]['dependencies'] = [];
            }

            $this->relationships[$className]['dependencies'][] = [
                'class' => $dependency['class'],
                'parameter' => $dependency['usage'],
                'context' => "Method Body: {$methodName}"
            ];

            // Recursively explore if it's an app class
            if (!$this->classResolver->shouldSkipClass($dependency['class'])) {
                $this->exploreClass($dependency['class'], $depth + 1, 'Runtime Dependency', $command);

                // If it's an Eloquent Model, explore relationships
                if ($this->typeDetectors->isEloquentModel($dependency['class'])) {
                    $this->exploreModelRelationships($dependency['class'], $depth + 1, $command);
                }
            }
        }
    }

    /**
     * Analyze source code for dependency patterns
     */
    private function analyzeSourceForDependencies(string $source): array
    {
        $dependencies = [];


        // START Patterns
        $dependencies = array_merge($dependencies, AuthUsers::detect($source));
        $dependencies = array_merge($dependencies, ExplicitModelStatic::detect($source));
        $dependencies = array_merge($dependencies, SimpleModelReferences::detect($source));
        $dependencies = array_merge($dependencies, NewClassName::detect($source));
        $dependencies = array_merge($dependencies, EventDispatch::detect($source));
        $dependencies = array_merge($dependencies, JobDispatching::detect($source));
        $dependencies = array_merge($dependencies, NotificationSending::detect($source));


        // Remove duplicates
        $unique = [];
        foreach ($dependencies as $dep) {
            $key = $dep['class'] . '|' . $dep['pattern'];
            if (!isset($unique[$key])) {
                $unique[$key] = $dep;
            }
        }

        return array_values($unique);
    }

    /**
     * Explore middleware applied to a route
     */
    private function exploreRouteMiddleware(Route $route, int $depth, Command $command): void
    {
        $middleware = $route->middleware();
        if (empty($middleware)) return;

        $indent = str_repeat('  ', $depth);
        $command->line("{$indent}🛡️  Middleware:");

        foreach ($middleware as $mw) {
            // Skip built-in middleware groups
            if (in_array($mw, ['web', 'api'])) {
                $command->line("{$indent}  ├─ {$mw} (group)");
                continue;
            }

            // Resolve middleware class from alias if needed
            $middlewareClass = $this->resolveMiddlewareClass($mw);
            if ($middlewareClass) {
                $command->line("{$indent}  ├─ {$mw} → {$middlewareClass}");

                // Explore the middleware class
                if (!$this->classResolver->shouldSkipClass($middlewareClass)) {
                    $this->exploreClass($middlewareClass, $depth + 2, 'Middleware', $command);
                }
            } else {
                $command->line("{$indent}  ├─ {$mw}");
            }
        }
    }

    /**
     * Resolve middleware alias to class name
     */
    private function resolveMiddlewareClass(string $middleware): ?string
    {
        // Handle class::method format (e.g., 'throttle:60,1')
        if (str_contains($middleware, ':')) {
            [$middleware] = explode(':', $middleware);
        }

        // If it's already a class name
        if ($this->classResolver->classExists($middleware)) {
            return $middleware;
        }

        // Try to resolve from app's Http/Kernel
        try {
            $kernel = app(\Illuminate\Contracts\Http\Kernel::class);

            // Use reflection to access the protected property
            $reflection = new ReflectionClass($kernel);

            // Try different property names based on Laravel version
            if ($reflection->hasProperty('routeMiddleware')) {
                $property = $reflection->getProperty('routeMiddleware');
                $property->setAccessible(true);
                $routeMiddleware = $property->getValue($kernel);
            } elseif ($reflection->hasProperty('middlewareAliases')) {
                // Laravel 10+ uses middlewareAliases
                $property = $reflection->getProperty('middlewareAliases');
                $property->setAccessible(true);
                $routeMiddleware = $property->getValue($kernel);
            } else {
                return null;
            }

            if (isset($routeMiddleware[$middleware])) {
                return $routeMiddleware[$middleware];
            }
        } catch (\Exception $e) {
            // If anything fails, just return null
            return null;
        }

        return null;
    }



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

        $reflection = $this->classResolver->getReflection($className);
        if (!$reflection) {
            $command->line(str_repeat('  ', $depth) . "❌ {$className} not found");
            return;
        }
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

    private function exploreMethodDependencies(ReflectionMethod $method, string $className, int $depth, string $context, Command $command): void
    {
        $parameters = $method->getParameters();
        $indent = str_repeat('  ', $depth);

        foreach ($parameters as $param) {
            $type = $param->getType();

            if (!$type || $type->isBuiltin()) continue;

            $typeName = $type->getName();

            if ($this->classResolver->shouldSkipClass($typeName)) {
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
        $reflection = $this->classResolver->getReflection($modelClass);
        if (!$reflection) return;
        $indent = str_repeat('  ', $depth);

        $command->line("{$indent}🗄️  Exploring Model relationships...");

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (
                str_starts_with($method->getName(), '__') ||
                $method->isStatic() ||
                $method->getNumberOfParameters() > 0
            ) {
                continue;
            }

            $returnType = $method->getReturnType();

            if ($returnType && $this->typeDetectors->isEloquentRelation($returnType->getName())) {
                $relationType = class_basename($returnType->getName());
                $command->line("{$indent}  🔗 {$method->getName()}(): {$relationType}");

                $stringHelper = new StringHelpers();
                $relatedModel = $stringHelper->guessRelatedModel($method->getName());
                if ($relatedModel && $this->classResolver->classExists($relatedModel)) {
                    $this->exploreClass($relatedModel, $depth + 1, "Related Model ({$relationType})", $command);
                }
            }
        }
    }

    /**
     * Validate security requirements before performing analysis
     * This is a backup security check in addition to middleware
     */
    private function validateSecurityRequirements(): void
    {
        $allowedEnvironments = config('routes-explorer.security.allowed_environments', ['local', 'development', 'testing']);
        $requireDebug = config('routes-explorer.security.require_debug', true);
        
        // Check environment
        $currentEnv = app()->environment();
        if (!in_array($currentEnv, $allowedEnvironments)) {
            throw new \RuntimeException(
                "Routes Explorer analysis is disabled in '{$currentEnv}' environment. " .
                "Only allowed in: " . implode(', ', $allowedEnvironments)
            );
        }
        
        // Check debug mode if required
        if ($requireDebug && !config('app.debug')) {
            throw new \RuntimeException(
                'Routes Explorer analysis requires debug mode to be enabled for security reasons.'
            );
        }
    }
}
