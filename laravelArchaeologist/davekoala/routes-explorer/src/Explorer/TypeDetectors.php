<?php

namespace DaveKoala\RoutesExplorer\Explorer;

use ReflectionClass;

/**
 * Type Detection Helper
 * 
 * Handles classification and detection of different class types.
 * This includes Laravel-specific patterns (Models, Controllers, etc.)
 * and general PHP patterns (interfaces, traits, abstract classes).
 */
class TypeDetectors
{
    /**
     * Check if a class is an Eloquent Model
     */
    public function isEloquentModel(string $className): bool
    {
        if (!class_exists($className)) return false;

        $reflection = new ReflectionClass($className);
        return $reflection->isSubclassOf('Illuminate\Database\Eloquent\Model');
    }

    /**
     * Check if a class is an Eloquent Relationship
     * 
     * Laravel has several relationship types that we want to detect
     * to understand model relationships in the application.
     */
    public function isEloquentRelation(string $className): bool
    {
        $relationTypes = [
            'Illuminate\Database\Eloquent\Relations\HasOne',
            'Illuminate\Database\Eloquent\Relations\HasMany',
            'Illuminate\Database\Eloquent\Relations\BelongsTo',
            'Illuminate\Database\Eloquent\Relations\BelongsToMany',
            'Illuminate\Database\Eloquent\Relations\MorphOne',
            'Illuminate\Database\Eloquent\Relations\MorphMany',
            'Illuminate\Database\Eloquent\Relations\MorphTo',
        ];

        foreach ($relationTypes as $relationType) {
            if ($className === $relationType || is_subclass_of($className, $relationType)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if we should skip exploring a framework class
     * 
     * We skip Laravel framework classes to focus on application-specific classes.
     * This keeps the output clean and relevant to the developer's own code.
     */
    public function shouldSkipFrameworkClass(string $className): bool
    {
        $frameworkPrefixes = [
            'Illuminate\\',
            'Symfony\\',
            'Psr\\',
            'Carbon\\',
            'Monolog\\',
        ];

        foreach ($frameworkPrefixes as $prefix) {
            if (str_starts_with($className, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get an emoji icon for a class based on its type
     * 
     * Visual indicators make the output much easier to scan and understand.
     */
    public function getClassEmoji(ReflectionClass $reflection): string
    {
        if ($reflection->isInterface()) return '🔌';
        if ($reflection->isTrait()) return '🧩';
        if ($this->isEloquentModel($reflection->getName())) return '🗄️';
        if (str_contains($reflection->getName(), 'Controller')) return '🎮';
        if (str_contains($reflection->getName(), 'Service')) return '⚙️';
        if ($reflection->isAbstract()) return '🔺';
        return '📦';
    }

    /**
     * Get a human-readable type description for a class
     */
    public function getClassType(ReflectionClass $reflection): string
    {
        if ($reflection->isInterface()) return 'Interface';
        if ($reflection->isTrait()) return 'Trait';
        if ($this->isEloquentModel($reflection->getName())) return 'Eloquent Model';
        if (str_contains($reflection->getName(), 'Controller')) return 'Controller';
        if (str_contains($reflection->getName(), 'Service')) return 'Service';
        if (str_contains($reflection->getName(), 'Middleware')) return 'Middleware';
        if (str_contains($reflection->getName(), 'Event')) return 'Event';
        if (str_contains($reflection->getName(), 'Job')) return 'Job';
        if (str_contains($reflection->getName(), 'Notification')) return 'Notification';
        if ($reflection->isAbstract()) return 'Abstract Class';
        if ($reflection->isFinal()) return 'Final Class';

        return 'Class';
    }

    /**
     * Get emoji for a class type (from string rather than ReflectionClass)
     * 
     * Used in output formatting when we only have the type string.
     */
    public function getClassEmojiFromType(string $type): string
    {
        return match ($type) {
            'Controller' => '🎮',
            'Eloquent Model' => '🗄️',
            'Service' => '⚙️',
            'Interface' => '🔌',
            'Trait' => '🧩',
            'Abstract Class' => '🔺',
            'Middleware' => '🛡️',
            'Event' => '📢',
            'Job' => '⚡',
            'Notification' => '📬',
            default => '📦'
        };
    }
}
