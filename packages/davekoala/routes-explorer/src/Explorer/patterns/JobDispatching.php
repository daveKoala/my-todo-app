<?php

namespace DaveKoala\RoutesExplorer\Explorer\Patterns;

use DaveKoala\RoutesExplorer\Explorer\RobustPatternMatcher;

class JobDispatching
{
    public static function detect(string $source): array
    {
        $dependencies = [];

        // Use robust pattern matching for job dispatching
        $jobMatches = RobustPatternMatcher::matchJobDispatch($source);
        
        foreach ($jobMatches as $match) {
            $jobClass = $match['class_name'];
            
            // Try different namespace possibilities
            $possibleClasses = [
                "App\\Jobs\\{$jobClass}",
                "App\\{$jobClass}",
                $jobClass // In case it's already fully qualified
            ];

            foreach ($possibleClasses as $fullClass) {
                if (class_exists($fullClass)) {
                    $dependencies[] = [
                        'class' => $fullClass,
                        'pattern' => trim($match['full_match']),
                        'usage' => 'job_dispatch'
                    ];
                    break; // Found it, stop trying other namespaces
                }
            }
        }

        // Remove duplicates
        return self::removeDuplicates($dependencies);
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
