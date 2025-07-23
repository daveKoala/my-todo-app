<?php

namespace DaveKoala\RoutesExplorer\Explorer\Patterns;

use DaveKoala\RoutesExplorer\Explorer\RobustPatternMatcher;

class ComprehensiveDependencies
{
    public static function detect(string $source): array
    {
        $dependencies = [];

        // Detect use statements (imports)
        $useMatches = RobustPatternMatcher::matchUseStatements($source);
        foreach ($useMatches as $match) {
            $fullClass = $match['full_class'];
            
            // Only include application classes (not framework imports)
            if (self::isApplicationClass($fullClass)) {
                $dependencies[] = [
                    'class' => $fullClass,
                    'pattern' => $match['full_match'],
                    'usage' => 'import'
                ];
            }
        }

        // Detect method parameters (route model binding, dependency injection)
        $paramMatches = RobustPatternMatcher::matchMethodParameters($source);
        foreach ($paramMatches as $match) {
            $className = $match['class_name'];
            
            // Try to resolve to full class name
            $possibleClasses = [
                "App\\Models\\{$className}",
                "App\\Http\\Requests\\{$className}",
                "App\\{$className}",
                $className
            ];

            foreach ($possibleClasses as $fullClass) {
                if (class_exists($fullClass)) {
                    $dependencies[] = [
                        'class' => $fullClass,
                        'pattern' => $match['full_match'],
                        'usage' => 'method_parameter'
                    ];
                    break;
                }
            }
        }

        // Detect relationship method calls
        $relationshipMatches = RobustPatternMatcher::matchRelationshipCalls($source);
        foreach ($relationshipMatches as $match) {
            $relationship = $match['relationship'];
            
            // Try to guess the related model from relationship name
            $modelName = self::guessModelFromRelationship($relationship);
            if ($modelName) {
                $possibleClasses = [
                    "App\\Models\\{$modelName}",
                    "App\\{$modelName}",
                ];

                foreach ($possibleClasses as $fullClass) {
                    if (class_exists($fullClass)) {
                        $dependencies[] = [
                            'class' => $fullClass,
                            'pattern' => $match['full_match'],
                            'usage' => 'relationship_method'
                        ];
                        break;
                    }
                }
            }
        }

        return self::removeDuplicates($dependencies);
    }

    /**
     * Check if a class is likely an application class (not framework)
     */
    private static function isApplicationClass(string $className): bool
    {
        $appPrefixes = ['App\\', 'Domain\\', 'Modules\\'];
        
        foreach ($appPrefixes as $prefix) {
            if (str_starts_with($className, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Guess model name from relationship name
     */
    private static function guessModelFromRelationship(string $relationship): ?string
    {
        // Common relationship naming patterns
        $patterns = [
            // posts -> Post
            '/^(\w+)s$/' => function($matches) {
                return ucfirst(rtrim($matches[1], 's'));
            },
            
            // notes -> Note  
            '/^(\w+)es$/' => function($matches) {
                return ucfirst(rtrim($matches[1], 'es'));
            },
            
            // categories -> Category
            '/^(\w+)ies$/' => function($matches) {
                return ucfirst(rtrim($matches[1], 'ies') . 'y');
            },
            
            // user -> User (singular)
            '/^(\w+)$/' => function($matches) {
                return ucfirst($matches[1]);
            },
        ];

        foreach ($patterns as $pattern => $transform) {
            if (preg_match($pattern, $relationship, $matches)) {
                return $transform($matches);
            }
        }

        return null;
    }

    /**
     * Remove duplicate dependencies
     */
    private static function removeDuplicates(array $dependencies): array
    {
        $unique = [];
        $seen = [];

        foreach ($dependencies as $dependency) {
            $key = $dependency['class'] . '|' . $dependency['usage'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $dependency;
            }
        }

        return $unique;
    }
}