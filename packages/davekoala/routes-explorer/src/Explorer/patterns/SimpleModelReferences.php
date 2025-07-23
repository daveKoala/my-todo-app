<?php

namespace DaveKoala\RoutesExplorer\Explorer\Patterns;

use DaveKoala\RoutesExplorer\Explorer\RobustPatternMatcher;

class SimpleModelReferences
{
    public static function detect(string $source): array
    {
        $dependencies = [];

        // Use robust pattern matching for model static calls
        $modelMatches = RobustPatternMatcher::matchModelStatic($source);
        
        foreach ($modelMatches as $match) {
            $modelName = $match['class_name'];
            
            // Skip common non-model static calls
            if (self::isFrameworkClass($modelName)) {
                continue;
            }

            // Try different namespace possibilities
            $possibleClasses = [
                "App\\Models\\{$modelName}",
                "App\\{$modelName}",
                $modelName // In case it's already fully qualified
            ];

            foreach ($possibleClasses as $fullClass) {
                if (class_exists($fullClass)) {
                    $dependencies[] = [
                        'class' => $fullClass,
                        'pattern' => "{$modelName}::{$match['method_name']}",
                        'usage' => 'model_static'
                    ];
                    break; // Found it, stop trying other namespaces
                }
            }
        }

        // Remove duplicates
        return self::removeDuplicates($dependencies);
    }

    /**
     * Check if a class name is likely a framework class
     */
    private static function isFrameworkClass(string $className): bool
    {
        $frameworkClasses = [
            'Auth', 'DB', 'Cache', 'Log', 'Route', 'Config', 'View', 'Session', 
            'Request', 'Response', 'Redirect', 'Hash', 'Str', 'Arr', 'Carbon', 
            'Event', 'Gate', 'Mail', 'Storage', 'Validator', 'Schema', 'Artisan',
            'Queue', 'Bus', 'Broadcast', 'Notification', 'Password', 'Cookie',
            'Crypt', 'File', 'Http', 'Lang', 'URL', 'Blade'
        ];

        return in_array($className, $frameworkClasses);
    }

    /**
     * Remove duplicate dependencies
     */
    private static function removeDuplicates(array $dependencies): array
    {
        $unique = [];
        $seen = [];

        foreach ($dependencies as $dependency) {
            $key = $dependency['class'] . '|' . $dependency['pattern'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $dependency;
            }
        }

        return $unique;
    }
}
