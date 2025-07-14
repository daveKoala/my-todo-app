<?php

namespace App\Console\Commands\ExploreRouteCommand;

use Illuminate\Routing\Route;
use Illuminate\Console\Command;
use ReflectionClass;
use ReflectionMethod;

/**
 * Class Analysis Engine
 * 
 * The heart of the exploration system. This handles:
 * - Parsing route actions to extract controller information
 * - Recursively exploring class relationships (inheritance, interfaces, traits)
 * - Analysing method dependencies (constructor and method parameters)
 * - Discovering Eloquent model relationships
 * 
 * Uses PHP's Reflection API extensively to introspect classes at runtime.
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
        
        // Extract controller and method from route action
        // Returns an array with route action details:
        /*
         * $action = [
         *   'controller' => 'App\Http\Controllers\NoteController@show',
         *   'uses' => 'App\Http\Controllers\NoteController@show',
         *   'middleware' => ['web'],
         *   'as' => 'notes.show',
         *   'where' => [],
         *   // ... other Laravel magic
         * ];
         */
        $action = $route->getAction();
        
        if (isset($action['controller'])) {
            [$controllerClass, $method] = explode('@', $action['controller']);
            
            $command->line("🎯 Starting from: {$controllerClass}@{$method}");
            $command->line('');
            
            // Explore the controller class
            $this->exploreClass($controllerClass, 0, 'Controller', $command);
            
            // Explore the specific method
            $this->exploreMethod($controllerClass, $method, 1, $command);
            
        } elseif (isset($action['uses']) && is_callable($action['uses'])) {
            $command->line("🎯 Route uses a Closure - exploring dependencies");
            // For closures, we'd need to analyze the closure itself
        } else {
            $command->warn("⚠️  Could not determine route action");
        }
        
        return $this->relationships;
    }
    
    /**
     * Recursively explore a class and all its relationships
     * 
     * This is where the recursion happens! The method explores:
     * 1. Inheritance chain (parent classes)
     * 2. Interface implementation  
     * 3. Trait usage
     * 4. Constructor dependencies
     * 
     * Recursion is controlled by:
     * - $this->analyzed[] array to prevent infinite loops
     * - $this->maxDepth to prevent going too deep
     */
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
        
        // Store relationship data for later analysis/output
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
        
        // 1. INHERITANCE - Explore parent classes (single inheritance in PHP)
        if ($parent = $reflection->getParentClass()) {
            $command->line("{$indent}  ↗️  Extends: {$parent->getName()}");
            $this->exploreClass($parent->getName(), $depth + 1, 'Parent Class', $command);
        }
        
        // 2. INTERFACES - Explore implemented interfaces (multiple allowed)
        foreach ($reflection->getInterfaces() as $interface) {
            $command->line("{$indent}  🔌 Implements: {$interface->getName()}");
            $this->exploreClass($interface->getName(), $depth + 1, 'Interface', $command);
        }
        
        // 3. TRAITS - Explore used traits (multiple allowed)
        foreach ($reflection->getTraits() as $trait) {
            $command->line("{$indent}  🧩 Uses Trait: {$trait->getName()}");
            $this->exploreClass($trait->getName(), $depth + 1, 'Trait', $command);
        }
        
        // 4. DEPENDENCIES - Explore constructor dependencies
        if ($constructor = $reflection->getConstructor()) {
            $this->exploreMethodDependencies($constructor, $className, $depth + 1, 'Constructor', $command);
        }
    }
    
    /**
     * Explore a specific method and its dependencies
     */
    private function exploreMethod(string $className, string $methodName, int $depth, Command $command): void
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
    
    /**
     * Analyze method parameters for dependency injection
     * 
     * This looks at method/constructor parameters and identifies:
     * - Type-hinted dependencies (non-built-in types)
     * - Laravel framework classes (which we skip to focus on app classes)
     * - Custom application classes (which we explore recursively)
     */
    private function exploreMethodDependencies(ReflectionMethod $method, string $className, int $depth, string $context, Command $command): void
    {
        $parameters = $method->getParameters();
        $indent = str_repeat('  ', $depth);
        
        foreach ($parameters as $param) {
            $type = $param->getType();
            
            // Skip built-in types (string, int, array, etc.)
            if (!$type || $type->isBuiltin()) continue;
            
            $typeName = $type->getName();
            
            // Skip Laravel framework classes to focus on app classes
            if ($this->typeDetectors->shouldSkipFrameworkClass($typeName)) {
                $command->line("{$indent}📋 {$param->getName()}: {$typeName} (Framework)");
                continue;
            }
            
            $command->line("{$indent}💉 Injected: {$param->getName()}: {$typeName}");
            
            // Store dependency information
            if (!isset($this->relationships[$className])) {
                $this->relationships[$className] = ['dependencies' => []];
            }
            
            $this->relationships[$className]['dependencies'][] = [
                'class' => $typeName,
                'parameter' => $param->getName(),
                'context' => $context
            ];
            
            // Recursively explore the dependency (this is where the magic happens!)
            $this->exploreClass($typeName, $depth + 1, 'Dependency', $command);
            
            // If it's an Eloquent Model, also explore its relationships
            if ($this->typeDetectors->isEloquentModel($typeName)) {
                $this->exploreModelRelationships($typeName, $depth + 1, $command);
            }
        }
    }
    
    /**
     * Explore Eloquent model relationships
     * 
     * This is Laravel-specific magic! It looks for methods that return
     * Eloquent relationship objects (HasOne, HasMany, BelongsTo, etc.)
     * and tries to determine what models they relate to.
     */
    private function exploreModelRelationships(string $modelClass, int $depth, Command $command): void
    {
        if (!class_exists($modelClass)) return;
        
        $reflection = new ReflectionClass($modelClass);
        $indent = str_repeat('  ', $depth);
        
        $command->line("{$indent}🗄️  Exploring Model relationships...");
        
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Skip magic methods and methods with parameters
            // (relationships are typically parameterless public methods)
            if (str_starts_with($method->getName(), '__') || 
                $method->isStatic() || 
                $method->getNumberOfParameters() > 0) {
                continue;
            }
            
            $returnType = $method->getReturnType();
            
            // Check if this method returns an Eloquent relationship
            if ($returnType && $this->typeDetectors->isEloquentRelation($returnType->getName())) {
                $relationType = class_basename($returnType->getName());
                $command->line("{$indent}  🔗 {$method->getName()}(): {$relationType}");
                
                // Try to guess the related model from the method name
                $stringHelper = new StringHelpers();
                $relatedModel = $stringHelper->guessRelatedModel($method->getName());
                if ($relatedModel && class_exists($relatedModel)) {
                    $this->exploreClass($relatedModel, $depth + 1, "Related Model ({$relationType})", $command);
                }
            }
        }
    }
}