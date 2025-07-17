<?php

namespace DaveKoala\RoutesExplorer\Explorer\Patterns;


class SimpleModelReferences
{
    public static function detect(string $source): array
    {
        $dependencies = [];

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

        return $dependencies;
    }
}
