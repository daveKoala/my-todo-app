<?php

namespace DaveKoala\RoutesExplorer\Explorer\Patterns;

class AuthUsers
{
    public static function detect(string $source): array
    {
        $dependencies = [];

        // Pattern 1: Auth::user() calls - get actual configured user model
        if (preg_match_all('/Auth::user\(\)/', $source, $matches)) {
            $userModel = self::getAuthUserModel();
            if ($userModel) {
                $dependencies[] = [
                    'class' => $userModel,
                    'pattern' => 'Auth::user()',
                    'usage' => 'auth_user'
                ];
            }
        }

        // Pattern 2: Auth::guard('name')->user() calls - check specific guard
        if (preg_match_all('/Auth::guard\([\'"]([^\'"]+)[\'"]\)->user\(\)/', $source, $matches)) {
            foreach ($matches[1] as $guardName) {
                $userModel = self::getAuthUserModel($guardName);
                if ($userModel) {
                    $dependencies[] = [
                        'class' => $userModel,
                        'pattern' => "Auth::guard('{$guardName}')->user()",
                        'usage' => 'auth_guard_user'
                    ];
                }
            }
        }

        // Pattern 6: $this->method() calls that might reference models
        if (preg_match_all('/\$this->(\w+)\(\)/', $source, $matches)) {
            foreach ($matches[1] as $methodCall) {
                // For getUser() type methods, try to determine what they return
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

        return $dependencies;
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
}
