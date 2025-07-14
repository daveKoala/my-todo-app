<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use ReflectionParameter;
use ReflectionMethod;
use ReflectionClass;

// This expands the Command class and was created wit Artisan
// php artisan make:command ExploreRouteCommand
class ExploreRouteCommand extends Command
{
    /**
     * The $signature Property:
     * This defines:
     * Command name: explore:route
     * Required argument: {route : Route name or URI}
     * Optional flags: {--depth=3} and {--format=table}
     */

    protected $signature = 'explore:route {route : Route name or URI} {--depth=3 : Maximum exploration depth} {--format=table : Output format (table|json|tree)}';
    
    protected $description = 'Explore a Laravel route and show all related classes, dependencies, and relationships';
    
    private array $analyzed = [];
    private array $relationships = [];
    private int $maxDepth;
    
    public function handle()
    {
        // Gets whatever is typed after the command name
        $routeIdentifier = $this->argument('route');
        $this->maxDepth = (int) $this->option('depth');
        $format = $this->option('format');
        
        $this->info("🔍 Exploring Laravel route: {$routeIdentifier}");
        $this->line(str_repeat('=', 60));
        
        try {
            // Find the route
            $route = $this->findRoute($routeIdentifier);
            
            if (!$route) {
                $this->error("Route '{$routeIdentifier}' not found!");
                $this->suggestSimilarRoutes($routeIdentifier);
                return Command::FAILURE;
            }
            
            // Display route info
            $this->displayRouteInfo($route);
            
            // Start exploration
            $this->analyzed = [];
            $this->relationships = [];
            
            $this->exploreRoute($route);
            
            // Display results
            match($format) {
                'json' => $this->outputJson(),
                'tree' => $this->outputTree(),
                default => $this->outputTable()
            };
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * This does what you expect. Using RouteFacade it will get a list of Routes and return the 'FIRST best matching route'
     * First by name then by partial match. Not perfect but good enough for this
     */
    private function findRoute(string $identifier): ?Route
    {
        $routes = RouteFacade::getRoutes();
        
        // Try to find by name first
        $route = $routes->getByName($identifier);
        if ($route) return $route;
        
        // Try to find by URI
        foreach ($routes as $route) {
            if ($route->uri() === ltrim($identifier, '/')) {
                return $route;
            }
        }
        
        // Try partial match
        foreach ($routes as $route) {
            if (str_contains($route->uri(), $identifier)) {
                return $route;
            }
        }
        
        return null;
    }
    
    private function displayRouteInfo(Route $route): void
    {
        $this->line('');
        $this->info('📍 Route Information:');
        $this->table(['Property', 'Value'], [
            ['URI', $route->uri()],
            ['Name', $route->getName() ?: 'N/A'],
            ['Methods', implode('|', $route->methods())],
            ['Action', $route->getActionName()],
            ['Middleware', implode(', ', $route->middleware()) ?: 'None'],
        ]);
        $this->line('');
    }
    
    private function exploreRoute(Route $route): void
    {
        $this->info('🔗 Exploring route chain...');
        $this->line('');
        
        // Extract controller and method from route action
        // Returns an array with route action details
        /**
         * $action = [
         * 'controller' => 'App\Http\Controllers\NoteController@show',
         * 'uses' => 'App\Http\Controllers\NoteController@show',
         * 'middleware' => ['web'],
         * 'as' => 'notes.show',
         * 'where' => [],
         * // ... other Laravel magic
         * ];
         */
        $action = $route->getAction();
        
        if (isset($action['controller'])) {
            [$controllerClass, $method] = explode('@', $action['controller']);
            
            $this->line("🎯 Starting from: {$controllerClass}@{$method}");
            $this->line('');
            
            // Explore the controller
            $this->exploreClass($controllerClass, 0, 'Controller');
            
            // Explore the specific method
            $this->exploreMethod($controllerClass, $method, 1);
            
        } elseif (isset($action['uses']) && is_callable($action['uses'])) {
            $this->line("🎯 Route uses a Closure - exploring dependencies");
            // For closures, we'd need to analyze the closure itself
        } else {
            $this->warn("⚠️  Could not determine route action");
        }
    }
    
    private function exploreClass(string $className, int $depth, string $context = ''): void
    {
        if (isset($this->analyzed[$className]) || $depth > $this->maxDepth) {
            if ($depth > $this->maxDepth) {
                $this->line(str_repeat('  ', $depth) . "⚠️  Max depth reached for {$className}");
            }
            return;
        }
        
        $this->analyzed[$className] = true;
        
        if (!class_exists($className)) {
            $this->line(str_repeat('  ', $depth) . "❌ {$className} not found");
            return;
        }
        
        $reflection = new ReflectionClass($className);
        $indent = str_repeat('  ', $depth);
        
        $this->line("{$indent}📦 {$this->getClassEmoji($reflection)} {$className} {$context}");
        
        // Store relationship data
        $this->relationships[$className] = [
            'name' => $className,
            'type' => $this->getClassType($reflection),
            'context' => $context,
            'depth' => $depth,
            'file' => $reflection->getFileName(),
            'extends' => ($parent = $reflection->getParentClass()) ? $parent->getName() : null,
            'implements' => array_keys($reflection->getInterfaces()),
            'traits' => array_keys($reflection->getTraits()),
            'dependencies' => []
        ];
        
        // Explore inheritance
        if ($parent = $reflection->getParentClass()) {
            $this->line("{$indent}  ↗️  Extends: {$parent->getName()}");
            $this->exploreClass($parent->getName(), $depth + 1, 'Parent Class');
        }
        
        // Explore interfaces
        foreach ($reflection->getInterfaces() as $interface) {
            $this->line("{$indent}  🔌 Implements: {$interface->getName()}");
            $this->exploreClass($interface->getName(), $depth + 1, 'Interface');
        }
        
        // Explore traits
        foreach ($reflection->getTraits() as $trait) {
            $this->line("{$indent}  🧩 Uses Trait: {$trait->getName()}");
            $this->exploreClass($trait->getName(), $depth + 1, 'Trait');
        }
        
        // Explore constructor dependencies
        if ($constructor = $reflection->getConstructor()) {
            $this->exploreMethodDependencies($constructor, $className, $depth + 1, 'Constructor');
        }
    }
    
    private function exploreMethod(string $className, string $methodName, int $depth): void
    {
        if (!class_exists($className)) return;
        
        $reflection = new ReflectionClass($className);
        
        if (!$reflection->hasMethod($methodName)) {
            $this->line(str_repeat('  ', $depth) . "❌ Method {$methodName} not found in {$className}");
            return;
        }
        
        $method = $reflection->getMethod($methodName);
        $indent = str_repeat('  ', $depth);
        
        $this->line("{$indent}🔧 Method: {$methodName}()");
        
        $this->exploreMethodDependencies($method, $className, $depth + 1, "Method: {$methodName}");
    }
    
    private function exploreMethodDependencies(ReflectionMethod $method, string $className, int $depth, string $context): void
    {
        $parameters = $method->getParameters();
        $indent = str_repeat('  ', $depth);
        
        foreach ($parameters as $param) {
            $type = $param->getType();
            
            if (!$type || $type->isBuiltin()) continue;
            
            $typeName = $type->getName();
            
            // Skip Laravel framework classes to focus on app classes
            if ($this->shouldSkipFrameworkClass($typeName)) {
                $this->line("{$indent}📋 {$param->getName()}: {$typeName} (Framework)");
                continue;
            }
            
            $this->line("{$indent}💉 Injected: {$param->getName()}: {$typeName}");
            
            // Store dependency
            if (!isset($this->relationships[$className])) {
                $this->relationships[$className] = ['dependencies' => []];
            }
            
            $this->relationships[$className]['dependencies'][] = [
                'class' => $typeName,
                'parameter' => $param->getName(),
                'context' => $context
            ];
            
            // Recursively explore the dependency
            $this->exploreClass($typeName, $depth + 1, 'Dependency');
            
            // If it's a Model, explore relationships
            if ($this->isEloquentModel($typeName)) {
                $this->exploreModelRelationships($typeName, $depth + 1);
            }
        }
    }
    
    private function exploreModelRelationships(string $modelClass, int $depth): void
    {
        if (!class_exists($modelClass)) return;
        
        $reflection = new ReflectionClass($modelClass);
        $indent = str_repeat('  ', $depth);
        
        $this->line("{$indent}🗄️  Exploring Model relationships...");
        
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Skip magic methods and non-relationship methods
            if (str_starts_with($method->getName(), '__') || 
                $method->isStatic() || 
                $method->getNumberOfParameters() > 0) {
                continue;
            }
            
            $returnType = $method->getReturnType();
            
            if ($returnType && $this->isEloquentRelation($returnType->getName())) {
                $relationType = class_basename($returnType->getName());
                $this->line("{$indent}  🔗 {$method->getName()}(): {$relationType}");
                
                // Try to determine related model
                $relatedModel = $this->guessRelatedModel($method->getName());
                if ($relatedModel && class_exists($relatedModel)) {
                    $this->exploreClass($relatedModel, $depth + 1, "Related Model ({$relationType})");
                }
            }
        }
    }
    
    private function isEloquentModel(string $className): bool
    {
        if (!class_exists($className)) return false;
        
        $reflection = new ReflectionClass($className);
        return $reflection->isSubclassOf('Illuminate\Database\Eloquent\Model');
    }
    
    private function isEloquentRelation(string $className): bool
    {
        $relationTypes = [
            'Illuminate\Database\Eloquent\Relations\HasOne',
            'Illuminate\Database\Eloquent\Relations\HasMany',
            'Illuminate\Database\Eloquent\Relations\BelongsTo',
            'Illuminate\Database\Eloquent\Relations\BelongsToMany',
            'Illuminate\Database\Eloquent\Relations\MorphOne',
            'Illuminate\Database\Eloquent\Relations\MorphMany',
            'Illuminate\Database\Eloquent\Relations\MorphTo',
        ];
        
        foreach ($relationTypes as $relationType) {
            if ($className === $relationType || is_subclass_of($className, $relationType)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function guessRelatedModel(string $relationshipName): ?string
    {
        // Simple heuristic to guess model name from relationship
        $modelName = ucfirst($this->makeSingular($relationshipName));
        return "App\\Models\\{$modelName}";
    }
    
    private function makeSingular(string $word): string
    {
        // Simple pluralization rules - not perfect but good enough for most cases
        $singularRules = [
            '/ies$/i' => 'y',      // categories -> category
            '/ves$/i' => 'f',      // lives -> life  
            '/ses$/i' => 's',      // classes -> class
            '/s$/i' => '',         // users -> user
        ];
        
        foreach ($singularRules as $pattern => $replacement) {
            if (preg_match($pattern, $word)) {
                return preg_replace($pattern, $replacement, $word);
            }
        }
        
        return $word; // Return as-is if no rule matches
    }
    
    private function shouldSkipFrameworkClass(string $className): bool
    {
        $frameworkPrefixes = [
            'Illuminate\\',
            'Symfony\\',
            'Psr\\',
            'Carbon\\',
            'Monolog\\',
        ];
        
        foreach ($frameworkPrefixes as $prefix) {
            if (str_starts_with($className, $prefix)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function getClassEmoji(ReflectionClass $reflection): string
    {
        if ($reflection->isInterface()) return '🔌';
        if ($reflection->isTrait()) return '🧩';
        if ($this->isEloquentModel($reflection->getName())) return '🗄️';
        if (str_contains($reflection->getName(), 'Controller')) return '🎮';
        if (str_contains($reflection->getName(), 'Service')) return '⚙️';
        if ($reflection->isAbstract()) return '🔺';
        return '📦';
    }
    
    private function getClassType(ReflectionClass $reflection): string
    {
        if ($reflection->isInterface()) return 'Interface';
        if ($reflection->isTrait()) return 'Trait';
        if ($this->isEloquentModel($reflection->getName())) return 'Eloquent Model';
        if (str_contains($reflection->getName(), 'Controller')) return 'Controller';
        if (str_contains($reflection->getName(), 'Service')) return 'Service';
        if ($reflection->isAbstract()) return 'Abstract Class';
        if ($reflection->isFinal()) return 'Final Class';
        return 'Class';
    }
    
    private function outputTable(): void
    {
        $this->line('');
        $this->info('📊 Exploration Summary:');
        
        $rows = [];
        foreach ($this->relationships as $class => $info) {
            $dependencies = isset($info['dependencies']) ? count($info['dependencies']) : 0;
            $implements = isset($info['implements']) ? count($info['implements']) : 0;
            $traits = isset($info['traits']) ? count($info['traits']) : 0;
            
            $rows[] = [
                $info['name'] ?? $class,
                $info['type'] ?? 'Unknown',
                $info['context'] ?? '',
                $info['extends'] ?? '',
                $implements,
                $traits,
                $dependencies
            ];
        }
        
        $this->table([
            'Class',
            'Type',
            'Context',
            'Extends',
            'Interfaces',
            'Traits',
            'Dependencies'
        ], $rows);
    }
    
    private function outputJson(): void
    {
        $this->line(json_encode($this->relationships, JSON_PRETTY_PRINT));
    }
    
    private function outputTree(): void
    {
        $this->info('🌳 Dependency Tree:');
        $this->displayTree($this->relationships, 0);
    }
    
    private function displayTree(array $relationships, int $depth): void
    {
        foreach ($relationships as $class => $info) {
            if (($info['depth'] ?? 0) === $depth) {
                $indent = str_repeat('  ', $depth);
                $emoji = $this->getClassEmojiFromType($info['type'] ?? '');
                $this->line("{$indent}{$emoji} {$class}");
            }
        }
    }
    
    private function getClassEmojiFromType(string $type): string
    {
        return match($type) {
            'Controller' => '🎮',
            'Eloquent Model' => '🗄️',
            'Service' => '⚙️',
            'Interface' => '🔌',
            'Trait' => '🧩',
            'Abstract Class' => '🔺',
            default => '📦'
        };
    }
    
    private function suggestSimilarRoutes(string $identifier): void
    {
        $this->line('');
        $this->info('💡 Did you mean one of these routes?');
        
        $routes = RouteFacade::getRoutes();
        $suggestions = [];
        
        foreach ($routes as $route) {
            if (str_contains($route->uri(), $identifier) || 
                str_contains($route->getName() ?? '', $identifier)) {
                $suggestions[] = [
                    $route->getName() ?? 'N/A',
                    $route->uri(),
                    implode('|', $route->methods())
                ];
            }
        }
        
        if (!empty($suggestions)) {
            $this->table(['Name', 'URI', 'Methods'], array_slice($suggestions, 0, 10));
        } else {
            $this->line('No similar routes found. Try: php artisan route:list');
        }
    }
}
?>