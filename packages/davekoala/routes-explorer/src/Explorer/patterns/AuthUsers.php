<?php

namespace DaveKoala\RoutesExplorer\Explorer\Patterns;

use DaveKoala\RoutesExplorer\Explorer\RobustPatternMatcher;

class AuthUsers
{
    public static function detect(string $source): array
    {
        $dependencies = [];

        // Use robust pattern matching for Auth::user() calls
        $authUserMatches = RobustPatternMatcher::matchAuthUser($source);
        foreach ($authUserMatches as $match) {
            $userModel = self::getAuthUserModel();
            if ($userModel) {
                $dependencies[] = [
                    'class' => $userModel,
                    'pattern' => trim($match['full_match']),
                    'usage' => 'auth_user'
                ];
            }
        }

        // Use robust pattern matching for Auth::guard() calls
        $authGuardMatches = RobustPatternMatcher::matchAuthGuard($source);
        foreach ($authGuardMatches as $match) {
            $guardName = $match['guard_name']; // May be null for variable guards
            $userModel = self::getAuthUserModel($guardName);
            if ($userModel) {
                $dependencies[] = [
                    'class' => $userModel,
                    'pattern' => trim($match['full_match']),
                    'usage' => 'auth_guard_user'
                ];
            }
        }

        // Helper methods (kept simple as they're less critical)
        if (preg_match_all('/\$this->(\w+)\(\)/', $source, $matches)) {
            foreach ($matches[1] as $methodCall) {
                if (str_contains(strtolower($methodCall), 'user')) {
                    $userModel = self::getAuthUserModel();
                    if ($userModel) {
                        $dependencies[] = [
                            'class' => $userModel,
                            'pattern' => "\$this->{$methodCall}()",
                            'usage' => 'helper_method'
                        ];
                    }
                }
            }
        }

        // Remove duplicates based on class and pattern
        return self::removeDuplicates($dependencies);
    }

    /**
     * Get the actual configured user model for auth.
     */
    private static function getAuthUserModel(?string $guard = null): ?string
    {
        try {
            // Get the guard configuration
            // Use default guard if none specified
            $guardName = $guard ?: config('auth.defaults.guard', 'web');
            $guardConfig = config("auth.guards.{$guardName}");

            if (!$guardConfig || !isset($guardConfig['provider'])) {
                return null;
            }

            // Get the provider configuration
            $providerName = $guardConfig['provider'];
            $providerConfig = config("auth.providers.{$providerName}");

            if (!$providerConfig || !isset($providerConfig['model'])) {
                return null;
            }

            $userModel = $providerConfig['model'];

            // Verify the model class actually exists
            if (class_exists($userModel)) {
                return $userModel;
            }

            return null;
        } catch (\Exception $e) {
            // If anything goes wrong, don't make assumptions
            return null;
        }
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
