<?php

namespace DaveKoala\RoutesExplorer\Explorer;

/**
 * Robust Pattern Matcher
 * 
 * Provides improved regex patterns that handle:
 * - Flexible whitespace
 * - Variable expressions  
 * - Multiple coding styles
 * - Multiline patterns
 */
class RobustPatternMatcher
{
    /**
     * Match Auth::user() calls with flexible whitespace
     */
    public static function matchAuthUser(string $source): array
    {
        $patterns = [
            // Standard: Auth::user()
            '/\bAuth\s*::\s*user\s*\(\s*\)/',
            
            // Variable: $auth::user()
            '/\$\w+\s*::\s*user\s*\(\s*\)/',
            
            // Helper function: auth()->user()
            '/\bauth\s*\(\s*\)\s*->\s*user\s*\(\s*\)/',
        ];

        $matches = [];
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $source, $patternMatches, PREG_OFFSET_CAPTURE)) {
                foreach ($patternMatches[0] as $match) {
                    $matches[] = [
                        'full_match' => $match[0],
                        'pattern_type' => 'auth_user',
                        'offset' => $match[1]
                    ];
                }
            }
        }

        return $matches;
    }

    /**
     * Match Auth::guard() calls with flexible parameters
     */
    public static function matchAuthGuard(string $source): array
    {
        $patterns = [
            // String literals: Auth::guard('name')
            '/\bAuth\s*::\s*guard\s*\(\s*[\'"]([^\'"]*)[\'\"]\s*\)\s*->\s*user\s*\(\s*\)/',
            
            // Variables: Auth::guard($var)  
            '/\bAuth\s*::\s*guard\s*\(\s*\$\w+\s*\)\s*->\s*user\s*\(\s*\)/',
            
            // Config calls: Auth::guard(config('auth.guard'))
            '/\bAuth\s*::\s*guard\s*\(\s*config\s*\([^)]+\)\s*\)\s*->\s*user\s*\(\s*\)/',
        ];

        $matches = [];
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $source, $patternMatches, PREG_OFFSET_CAPTURE)) {
                foreach ($patternMatches[0] as $index => $match) {
                    $guardName = $patternMatches[1][$index][0] ?? null; // Only for string literals
                    $matches[] = [
                        'full_match' => $match[0],
                        'guard_name' => $guardName,
                        'pattern_type' => 'auth_guard',
                        'offset' => $match[1]
                    ];
                }
            }
        }

        return $matches;
    }

    /**
     * Match job dispatching with flexible syntax
     */
    public static function matchJobDispatch(string $source): array
    {
        $patterns = [
            // dispatch(new JobClass())
            '/\bdispatch\s*\(\s*new\s+(\w+)\s*\(/',
            
            // dispatch(new JobClass($param))
            '/\bdispatch\s*\(\s*new\s+(\w+)\s*\([^)]*\)/',
            
            // JobClass::dispatch()
            '/\b([A-Z]\w*)::\s*dispatch\s*\(/',
            
            // dispatch(JobClass::class)
            '/\bdispatch\s*\(\s*(\w+)::\s*class/',
        ];

        $matches = [];
        foreach ($patterns as $patternIndex => $pattern) {
            if (preg_match_all($pattern, $source, $patternMatches, PREG_OFFSET_CAPTURE)) {
                foreach ($patternMatches[1] as $index => $classMatch) {
                    $className = $classMatch[0];
                    $fullMatch = $patternMatches[0][$index][0];
                    
                    // Only process if it looks like a class name (starts with uppercase)
                    if (ctype_upper($className[0])) {
                        $matches[] = [
                            'full_match' => $fullMatch,
                            'class_name' => $className,
                            'pattern_type' => 'job_dispatch',
                            'pattern_variant' => $patternIndex,
                            'offset' => $patternMatches[0][$index][1]
                        ];
                    }
                }
            }
        }

        return $matches;
    }

    /**
     * Match model static calls with namespace flexibility
     */
    public static function matchModelStatic(string $source): array
    {
        $patterns = [
            // Simple: User::find() (with flexible whitespace)
            '/\b([A-Z]\w*)\s*::\s*(\w+)\s*\(/',
            
            // Namespaced: App\Models\User::find()
            '/\\\\?(?:App\\\\Models\\\\|Models\\\\)?([A-Z]\w*)\s*::\s*(\w+)\s*\(/',
            
            // Variable: $model::find()
            '/\$\w+\s*::\s*(\w+)\s*\(/',
        ];

        $matches = [];
        $modelMethods = ['find', 'create', 'where', 'first', 'all', 'get', 'with', 'has', 'doesntHave'];

        foreach ($patterns as $patternIndex => $pattern) {
            if (preg_match_all($pattern, $source, $patternMatches, PREG_OFFSET_CAPTURE)) {
                foreach ($patternMatches[0] as $index => $fullMatch) {
                    $className = $patternMatches[1][$index][0] ?? null;
                    $methodName = $patternMatches[2][$index][0] ?? $patternMatches[1][$index][0] ?? null;
                    
                    // Only process if it's likely a model method
                    if ($className && in_array($methodName, $modelMethods)) {
                        $matches[] = [
                            'full_match' => $fullMatch[0],
                            'class_name' => $className,
                            'method_name' => $methodName,
                            'pattern_type' => 'model_static',
                            'pattern_variant' => $patternIndex,
                            'offset' => $fullMatch[1]
                        ];
                    }
                }
            }
        }

        return $matches;
    }

    /**
     * Match event dispatching patterns  
     */
    public static function matchEventDispatch(string $source): array
    {
        $patterns = [
            // event(new EventClass())
            '/\bevent\s*\(\s*new\s+(\w+)\s*\(/',
            
            // Event::dispatch()
            '/\b([A-Z]\w*Event)::\s*dispatch\s*\(/',
            
            // event(EventClass::class)
            '/\bevent\s*\(\s*(\w+)::\s*class/',
        ];

        $matches = [];
        foreach ($patterns as $patternIndex => $pattern) {
            if (preg_match_all($pattern, $source, $patternMatches, PREG_OFFSET_CAPTURE)) {
                foreach ($patternMatches[1] as $index => $classMatch) {
                    $className = $classMatch[0];
                    $fullMatch = $patternMatches[0][$index][0];
                    
                    if (ctype_upper($className[0])) {
                        $matches[] = [
                            'full_match' => $fullMatch,
                            'class_name' => $className,
                            'pattern_type' => 'event_dispatch',
                            'pattern_variant' => $patternIndex,
                            'offset' => $patternMatches[0][$index][1]
                        ];
                    }
                }
            }
        }

        return $matches;
    }

    /**
     * Match new class instantiations
     */
    public static function matchNewClass(string $source): array
    {
        $patterns = [
            // new ClassName()
            '/\bnew\s+([A-Z]\w*)\s*\(/',
            
            // new \App\Models\ClassName()
            '/\bnew\s+\\\\?(?:App\\\\)?(?:\w+\\\\)*([A-Z]\w*)\s*\(/',
        ];

        $matches = [];
        foreach ($patterns as $patternIndex => $pattern) {
            if (preg_match_all($pattern, $source, $patternMatches, PREG_OFFSET_CAPTURE)) {
                foreach ($patternMatches[1] as $index => $classMatch) {
                    $className = $classMatch[0];
                    $fullMatch = $patternMatches[0][$index][0];
                    
                    $matches[] = [
                        'full_match' => $fullMatch,
                        'class_name' => $className,
                        'pattern_type' => 'new_class',
                        'pattern_variant' => $patternIndex,
                        'offset' => $patternMatches[0][$index][1]
                    ];
                }
            }
        }

        return $matches;
    }

    /**
     * Match use statements (imports)
     */
    public static function matchUseStatements(string $source): array
    {
        $patterns = [
            // use App\Models\ClassName;
            '/^use\s+([A-Za-z\\\\]+);/m',
            
            // use App\Models\{Class1, Class2};
            '/^use\s+([A-Za-z\\\\]+)\\\\\{([^}]+)\};/m',
        ];

        $matches = [];
        foreach ($patterns as $patternIndex => $pattern) {
            if (preg_match_all($pattern, $source, $patternMatches, PREG_OFFSET_CAPTURE)) {
                if ($patternIndex === 0) {
                    // Single use statement
                    foreach ($patternMatches[1] as $index => $classMatch) {
                        $fullClass = $classMatch[0];
                        $className = basename(str_replace('\\', '/', $fullClass));
                        
                        $matches[] = [
                            'full_match' => trim($patternMatches[0][$index][0]),
                            'class_name' => $className,
                            'full_class' => $fullClass,
                            'pattern_type' => 'use_statement',
                            'offset' => $patternMatches[0][$index][1]
                        ];
                    }
                } else {
                    // Group use statement
                    foreach ($patternMatches[1] as $index => $namespaceMatch) {
                        $namespace = $namespaceMatch[0];
                        $classes = explode(',', $patternMatches[2][$index][0]);
                        
                        foreach ($classes as $class) {
                            $class = trim($class);
                            $fullClass = $namespace . '\\' . $class;
                            
                            $matches[] = [
                                'full_match' => trim($patternMatches[0][$index][0]),
                                'class_name' => $class,
                                'full_class' => $fullClass,
                                'pattern_type' => 'use_statement',
                                'offset' => $patternMatches[0][$index][1]
                            ];
                        }
                    }
                }
            }
        }

        return $matches;
    }

    /**
     * Match method parameters with type hints
     */
    public static function matchMethodParameters(string $source): array
    {
        $patterns = [
            // public function method(ClassName $param)
            '/(?:public|private|protected)?\s*function\s+\w+\s*\([^)]*([A-Z]\w+)\s+\$\w+/m',
            
            // function method(ClassName $param, OtherClass $other)
            '/function\s+\w+\s*\([^)]*([A-Z]\w+)\s+\$\w+/m',
        ];

        $matches = [];
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $source, $patternMatches, PREG_OFFSET_CAPTURE)) {
                foreach ($patternMatches[1] as $index => $classMatch) {
                    $className = $classMatch[0];
                    $fullMatch = $patternMatches[0][$index][0];
                    
                    $matches[] = [
                        'full_match' => trim($fullMatch),
                        'class_name' => $className,
                        'pattern_type' => 'method_parameter',
                        'offset' => $patternMatches[0][$index][1]
                    ];
                }
            }
        }

        return $matches;
    }

    /**
     * Match relationship method calls like $user->notes()->create()
     */
    public static function matchRelationshipCalls(string $source): array
    {
        $patterns = [
            // $model->relationship()->method()
            '/\$\w+->(\w+)\(\)->(\w+)\s*\(/',
            
            // $this->relationship()->method()
            '/\$this->(\w+)\(\)->(\w+)\s*\(/',
        ];

        $matches = [];
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $source, $patternMatches, PREG_OFFSET_CAPTURE)) {
                foreach ($patternMatches[0] as $index => $fullMatch) {
                    $relationship = $patternMatches[1][$index][0];
                    $method = $patternMatches[2][$index][0];
                    
                    // Only include if it looks like a model method
                    $modelMethods = ['create', 'update', 'delete', 'save', 'find', 'where', 'first', 'get', 'all'];
                    if (in_array($method, $modelMethods)) {
                        $matches[] = [
                            'full_match' => trim($fullMatch[0]),
                            'relationship' => $relationship,
                            'method' => $method,
                            'pattern_type' => 'relationship_call',
                            'offset' => $fullMatch[1]
                        ];
                    }
                }
            }
        }

        return $matches;
    }

    /**
     * Remove duplicate matches based on offset and class name
     */
    public static function removeDuplicateMatches(array $matches): array
    {
        $unique = [];
        $seen = [];

        foreach ($matches as $match) {
            $key = ($match['offset'] ?? 0) . '|' . ($match['class_name'] ?? $match['pattern_type']);
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $match;
            }
        }

        return $unique;
    }
}