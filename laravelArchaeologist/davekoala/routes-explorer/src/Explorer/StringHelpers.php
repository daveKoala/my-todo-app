<?php

namespace DaveKoala\RoutesExplorer\Explorer;

/**
 * String Helper Utilities
 * 
 * Helper functions for string manipulation, primarily around
 * guessing model names from relationship method names.
 * 
 * Note: Laravel used to have str_singular() but it was removed in newer versions,
 * so we implement our own simple version here.
 */
class StringHelpers
{
    /**
     * Guess the related model class name from a relationship method name
     * 
     * This uses simple heuristics to convert relationship method names
     * like "users()" -> "App\Models\User" or "posts()" -> "App\Models\Post"
     */
    public function guessRelatedModel(string $relationshipName): ?string
    {
        // Simple heuristic to guess model name from relationship
        $modelName = ucfirst($this->makeSingular($relationshipName));
        return "App\\Models\\{$modelName}";
    }
    
    /**
     * Convert plural words to singular
     * 
     * Simple pluralization rules - not perfect but good enough for most cases.
     * This replaces Laravel's removed str_singular() helper.
     */
    public function makeSingular(string $word): string
    {
        // Simple pluralization rules - not perfect but good enough for most cases
        $singularRules = [
            '/ies$/i' => 'y',      // categories -> category
            '/ves$/i' => 'f',      // lives -> life  
            '/ses$/i' => 's',      // classes -> class
            '/s$/i' => '',         // users -> user
        ];
        
        foreach ($singularRules as $pattern => $replacement) {
            if (preg_match($pattern, $word)) {
                return preg_replace($pattern, $replacement, $word);
            }
        }
        
        return $word; // Return as-is if no rule matches
    }

    public function getVerb(string $word): string 
    {
        $wordArray = explode('|', $word);

        return $wordArray[0];
    }
}