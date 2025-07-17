<?php

namespace DaveKoala\RoutesExplorer\Explorer\Patterns;


class NewClassName
{
    public static function detect(string $source): array
    {
        $dependencies = [];

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

        return $dependencies;
    }
}
