<?php

namespace DaveKoala\RoutesExplorer\Explorer;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use ReflectionMethod;
use ReflectionClass;

/**
 * Enhanced Class Analysis Engine
 * 
 * Also includes method body analysis to detect additional dependencies
 * like Auth::user(), static calls, and class instantiations.
 * 
 * The 'Command' stuff is legacy from when I first built this as an Artisan tool.
 */
class ClassAnalysisEngine
{
    private array $analyzed = [];
    private array $relationships = [];
    private int $maxDepth;
    private TypeDetectors $typeDetectors;

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
        if (!class_exists($className)) return;

        $reflection = new ReflectionClass($className);

        if (!$reflection->hasMethod($methodName)) {
            $command->line(str_repeat('  ', $depth) . "❌ Method {$methodName} not found in {$className}");
            return;
        }

        $method = $reflection->getMethod($methodName);
        // PHP has some odd methods, but then found of JS has  <>.repeat(3)
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
            if (!$this->typeDetectors->shouldSkipFrameworkClass($dependency['class'])) {
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

        // Pattern 1: Auth::user() calls - get actual configured user model
        if (preg_match_all('/Auth::user\(\)/', $source, $matches)) {
            $userModel = $this->getAuthUserModel();
            if ($userModel) {
                $dependencies[] = [
                    'class' => $userModel,
                    'pattern' => 'Auth::user()',
                    'usage' => 'auth_user'
                ];
            }
        }

        // Pattern 2: Auth::guard('name')->user() calls - check specific guard
        if (preg_match_all('/Auth::guard\([\'"]([^\'"]+)[\'"]\)->user\(\)/', $source, $matches)) {
            foreach ($matches[1] as $guardName) {
                $userModel = $this->getAuthUserModel($guardName);
                if ($userModel) {
                    $dependencies[] = [
                        'class' => $userModel,
                        'pattern' => "Auth::guard('{$guardName}')->user()",
                        'usage' => 'auth_guard_user'
                    ];
                }
            }
        }

        // Pattern 3: Explicit model static calls like User::first(), Customer::create()
        if (preg_match_all('/\\\\?App\\\\Models\\\\(\w+)::/', $source, $matches)) {
            foreach ($matches[1] as $model) {
                $fullClass = "App\\Models\\{$model}";
                if (class_exists($fullClass)) {
                    $dependencies[] = [
                        'class' => $fullClass,
                        'pattern' => "App\\Models\\{$model}::",
                        'usage' => 'static_call'
                    ];
                }
            }
        }

        // Pattern 4: Simple model references - but verify they exist
        if (preg_match_all('/(\w+)::(?:first|create|find|where|all|factory)\(/', $source, $matches)) {
            foreach ($matches[1] as $possibleModel) {
                // Skip known non-models
                if (in_array($possibleModel, ['Auth', 'DB', 'Cache', 'Log', 'Mail', 'Queue'])) {
                    continue;
                }

                // Only consider if it looks like a model (starts with capital)
                if (ctype_upper($possibleModel[0])) {
                    $fullClass = "App\\Models\\{$possibleModel}";
                    if (class_exists($fullClass)) {
                        $dependencies[] = [
                            'class' => $fullClass,
                            'pattern' => "{$possibleModel}::",
                            'usage' => 'static_call'
                        ];
                    }
                }
            }
        }

        // Pattern 5: new ClassName() instantiations
        if (preg_match_all('/new\s+\\\\?(\w+(?:\\\\[\w]+)*)\s*\(/', $source, $matches)) {
            foreach ($matches[1] as $class) {
                $fullClass = str_starts_with($class, 'App\\') ? $class : "App\\{$class}";
                if (class_exists($fullClass)) {
                    $dependencies[] = [
                        'class' => $fullClass,
                        'pattern' => "new {$class}()",
                        'usage' => 'instantiation'
                    ];
                }
            }
        }

        // Pattern 6: $this->method() calls that might reference models
        if (preg_match_all('/\$this->(\w+)\(\)/', $source, $matches)) {
            foreach ($matches[1] as $methodCall) {
                // For getUser() type methods, try to determine what they return
                if (str_contains(strtolower($methodCall), 'user')) {
                    $userModel = $this->getAuthUserModel();
                    if ($userModel) {
                        $dependencies[] = [
                            'class' => $userModel,
                            'pattern' => "\$this->{$methodCall}()",
                            'usage' => 'helper_method'
                        ];
                    }
                }
            }
        }

        // Pattern 7: Event dispatching
        if (preg_match_all('/event\s*\(\s*new\s+(\w+)\s*\(/', $source, $matches)) {
            foreach ($matches[1] as $eventClass) {
                $fullClass = "App\\Events\\{$eventClass}";
                if (class_exists($fullClass)) {
                    $dependencies[] = [
                        'class' => $fullClass,
                        'pattern' => "event(new {$eventClass}())",
                        'usage' => 'event_dispatch'
                    ];
                }
            }
        }

        // Pattern 8: Event::dispatch or EventName::dispatch
        if (preg_match_all('/(\w+)::dispatch\s*\(/', $source, $matches)) {
            foreach ($matches[1] as $possibleEvent) {
                if ($possibleEvent !== 'Event' && ctype_upper($possibleEvent[0])) {
                    $fullClass = "App\\Events\\{$possibleEvent}";
                    if (class_exists($fullClass)) {
                        $dependencies[] = [
                            'class' => $fullClass,
                            'pattern' => "{$possibleEvent}::dispatch()",
                            'usage' => 'event_dispatch'
                        ];
                    }
                }
            }
        }

        // Pattern 9: Job dispatching
        if (preg_match_all('/dispatch\s*\(\s*new\s+(\w+)\s*\(/', $source, $matches)) {
            foreach ($matches[1] as $jobClass) {
                $fullClass = "App\\Jobs\\{$jobClass}";
                if (class_exists($fullClass)) {
                    $dependencies[] = [
                        'class' => $fullClass,
                        'pattern' => "dispatch(new {$jobClass}())",
                        'usage' => 'job_dispatch'
                    ];
                }
            }
        }

        // Pattern 10: Job::dispatch pattern
        if (preg_match_all('/(\w+)::dispatch\s*\(/', $source, $matches)) {
            foreach ($matches[1] as $possibleJob) {
                if (ctype_upper($possibleJob[0])) {
                    $fullClass = "App\\Jobs\\{$possibleJob}";
                    if (class_exists($fullClass)) {
                        $dependencies[] = [
                            'class' => $fullClass,
                            'pattern' => "{$possibleJob}::dispatch()",
                            'usage' => 'job_dispatch'
                        ];
                    }
                }
            }
        }

        // Pattern 11: Notification sending
        if (preg_match_all('/->notify\s*\(\s*new\s+(\w+)\s*\(/', $source, $matches)) {
            foreach ($matches[1] as $notificationClass) {
                $fullClass = "App\\Notifications\\{$notificationClass}";
                if (class_exists($fullClass)) {
                    $dependencies[] = [
                        'class' => $fullClass,
                        'pattern' => "->notify(new {$notificationClass}())",
                        'usage' => 'notification'
                    ];
                }
            }
        }

        // Pattern 12: Notification::send
        if (preg_match_all('/Notification::send\s*\([^,]+,\s*new\s+(\w+)\s*\(/', $source, $matches)) {
            foreach ($matches[1] as $notificationClass) {
                $fullClass = "App\\Notifications\\{$notificationClass}";
                if (class_exists($fullClass)) {
                    $dependencies[] = [
                        'class' => $fullClass,
                        'pattern' => "Notification::send(..., new {$notificationClass}())",
                        'usage' => 'notification'
                    ];
                }
            }
        }

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
            if ($middlewareClass && class_exists($middlewareClass)) {
                $command->line("{$indent}  ├─ {$mw} → {$middlewareClass}");

                // Explore the middleware class
                if (!$this->typeDetectors->shouldSkipFrameworkClass($middlewareClass)) {
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
        // Handle class::method format
        if (str_contains($middleware, ':')) {
            [$middleware] = explode(':', $middleware);
        }

        // If it's already a class name
        if (class_exists($middleware)) {
            return $middleware;
        }

        // Try to resolve from app's Http/Kernel
        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);

        // Check route middleware
        $routeMiddleware = $kernel->getRouteMiddleware();
        if (isset($routeMiddleware[$middleware])) {
            return $routeMiddleware[$middleware];
        }

        return null;
    }

    /**
     * Get the actual configured user model for auth
     */
    private function getAuthUserModel(string $guard = null): ?string
    {
        try {
            // Get the guard configuration
            $guardName = $guard ?: config('auth.defaults.guard', 'web');
            $guardConfig = config("auth.guards.{$guardName}");

            if (!$guardConfig || !isset($guardConfig['provider'])) {
                return null;
            }

            // Get the provider configuration
            $providerName = $guardConfig['provider'];
            $providerConfig = config("auth.providers.{$providerName}");

            if (!$providerConfig || !isset($providerConfig['model'])) {
                return null;
            }

            $userModel = $providerConfig['model'];

            // Verify the model class actually exists
            if (class_exists($userModel)) {
                return $userModel;
            }

            return null;
        } catch (\Exception $e) {
            // If anything goes wrong, don't make assumptions
            return null;
        }
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

        if (!class_exists($className)) {
            $command->line(str_repeat('  ', $depth) . "❌ {$className} not found");
            return;
        }

        $reflection = new ReflectionClass($className);
        $indent = str_repeat('  ', $depth);

        $command->line("{$indent}📦 {$this->typeDetectors->getClassEmoji($reflection)} {$className} {$context}");

        // Store relationship data
        $this->relationships[$className] = [
            'name' => $className,
            'type' => $this->typeDetectors->getClassType($reflection),
            'context' => $context,
            'depth' => $depth,
            'file' => $reflection->getFileName(),
            'extends' => ($parent = $reflection->getParentClass()) ? $parent->getName() : null,
            'implements' => array_keys($reflection->getInterfaces()),
            'traits' => array_keys($reflection->getTraits()),
            'dependencies' => []
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
        if (!class_exists($modelClass)) return;

        $reflection = new ReflectionClass($modelClass);
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
                if ($relatedModel && class_exists($relatedModel)) {
                    $this->exploreClass($relatedModel, $depth + 1, "Related Model ({$relationType})", $command);
                }
            }
        }
    }
}
