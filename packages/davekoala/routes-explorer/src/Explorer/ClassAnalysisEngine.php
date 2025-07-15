<?php

namespace DaveKoala\RoutesExplorer\Explorer;

use Illuminate\Routing\Route;
use Illuminate\Console\Command;
use ReflectionClass;
use ReflectionMethod;

/**
 * Enhanced Class Analysis Engine
 * 
 * Now includes method body analysis to detect additional dependencies
 * like Auth::user(), static calls, and class instantiations.
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
        $indent = str_repeat('  ', $depth);
        
        $command->line("{$indent}🔧 Method: {$methodName}()");
        
        // Original parameter-based dependency analysis
        $this->exploreMethodDependencies($method, $className, $depth + 1, "Method: {$methodName}", $command);
        
        // NEW: Method body analysis for additional dependencies
        $this->exploreMethodBodyDependencies($reflection, $methodName, $className, $depth + 1, $command);
    }
    
    /**
     * NEW: Analyze method body for additional dependencies
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
        
        // Pattern 1: Auth::user() calls
        if (preg_match_all('/Auth::user\(\)/', $source, $matches)) {
            $dependencies[] = [
                'class' => 'App\\Models\\User',
                'pattern' => 'Auth::user()',
                'usage' => 'auth_user'
            ];
        }
        
        // Pattern 2: User model static calls like User::first(), User::create()
        if (preg_match_all('/\\\\?App\\\\Models\\\\(\w+)::/', $source, $matches)) {
            foreach ($matches[1] as $model) {
                $dependencies[] = [
                    'class' => "App\\Models\\{$model}",
                    'pattern' => "App\\Models\\{$model}::",
                    'usage' => 'static_call'
                ];
            }
        }
        
        // Pattern 3: Simple model references like User::first() (without full namespace)
        if (preg_match_all('/(\w+)::(?:first|create|find|where|all)\(/', $source, $matches)) {
            foreach ($matches[1] as $possibleModel) {
                // Only consider if it looks like a model (starts with capital)
                if (ctype_upper($possibleModel[0]) && $possibleModel !== 'Auth') {
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
        
        // Pattern 4: new ClassName() instantiations
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
        
        // Pattern 5: $this->method() calls that might reference models
        // This is more complex but could detect calls to getUser() etc.
        if (preg_match_all('/\$this->(\w+)\(\)/', $source, $matches)) {
            // For now, just detect getUser() specifically since we know about it
            foreach ($matches[1] as $methodCall) {
                if ($methodCall === 'getUser') {
                    $dependencies[] = [
                        'class' => 'App\\Models\\User',
                        'pattern' => '$this->getUser()',
                        'usage' => 'helper_method'
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
    
    // ... rest of the original methods remain the same ...
    
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
            if (str_starts_with($method->getName(), '__') || 
                $method->isStatic() || 
                $method->getNumberOfParameters() > 0) {
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