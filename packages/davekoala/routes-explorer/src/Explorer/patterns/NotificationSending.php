<?php

namespace DaveKoala\RoutesExplorer\Explorer\Patterns;


class NotificationSending
{
    public static function detect(string $source): array
    {
        $dependencies = [];


        // Pattern 11: Notification sending
        if (preg_match_all('/->notify\s*\(\s*new\s+(\w+)\s*\(/', $source, $matches)) {
            foreach ($matches[1] as $notificationClass) {
                $fullClass = "App\\Notifications\\{$notificationClass}";
                if (class_exists($fullClass)) {
                    $dependencies[] = [
                        'class' => $fullClass,
                        'pattern' => "->notify(new {$notificationClass}())",
                        'usage' => 'notification'
                    ];
                }
            }
        }

        // Pattern 12: Notification::send
        if (preg_match_all('/Notification::send\s*\([^,]+,\s*new\s+(\w+)\s*\(/', $source, $matches)) {
            foreach ($matches[1] as $notificationClass) {
                $fullClass = "App\\Notifications\\{$notificationClass}";
                if (class_exists($fullClass)) {
                    $dependencies[] = [
                        'class' => $fullClass,
                        'pattern' => "Notification::send(..., new {$notificationClass}())",
                        'usage' => 'notification'
                    ];
                }
            }
        }

        return $dependencies;
    }
}
