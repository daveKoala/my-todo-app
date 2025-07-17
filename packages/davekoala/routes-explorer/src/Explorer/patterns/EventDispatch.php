<?php

namespace DaveKoala\RoutesExplorer\Explorer\Patterns;


class EventDispatch
{
    public static function detect(string $source): array
    {
        $dependencies = [];

        // Pattern 7: Event dispatching
        if (preg_match_all('/event\s*\(\s*new\s+(\w+)\s*\(/', $source, $matches)) {
            foreach ($matches[1] as $eventClass) {
                $fullClass = "App\\Events\\{$eventClass}";
                if (class_exists($fullClass)) {
                    $dependencies[] = [
                        'class' => $fullClass,
                        'pattern' => "event(new {$eventClass}())",
                        'usage' => 'event_dispatch'
                    ];
                }
            }
        }

        // Pattern 8: Event::dispatch or EventName::dispatch
        if (preg_match_all('/(\w+)::dispatch\s*\(/', $source, $matches)) {
            foreach ($matches[1] as $possibleEvent) {
                if ($possibleEvent !== 'Event' && ctype_upper($possibleEvent[0])) {
                    $fullClass = "App\\Events\\{$possibleEvent}";
                    if (class_exists($fullClass)) {
                        $dependencies[] = [
                            'class' => $fullClass,
                            'pattern' => "{$possibleEvent}::dispatch()",
                            'usage' => 'event_dispatch'
                        ];
                    }
                }
            }
        }

        return $dependencies;
    }
}
