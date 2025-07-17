<?php

namespace DaveKoala\RoutesExplorer\Explorer\Patterns;


class JobDispatching
{
    public static function detect(string $source): array
    {
        $dependencies = [];

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

        return $dependencies;
    }
}
