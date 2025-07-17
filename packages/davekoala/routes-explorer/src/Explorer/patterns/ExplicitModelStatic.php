<?php

namespace DaveKoala\RoutesExplorer\Explorer\Patterns;


class ExplicitModelStatic
{
    public static function detect(string $source): array
    {
        $dependencies = [];

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

        return $dependencies;
    }
}
