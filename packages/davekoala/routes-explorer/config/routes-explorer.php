<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Namespaces
    |--------------------------------------------------------------------------
    |
    | Configure the base namespaces for your Laravel application.
    | The package will use these to resolve and analyze classes.
    |
    */
    'namespaces' => [
        'app' => 'App\\',
        'models' => 'App\\Models\\',
        'controllers' => 'App\\Http\\Controllers\\',
        'middleware' => 'App\\Http\\Middleware\\',
        'jobs' => 'App\\Jobs\\',
        'events' => 'App\\Events\\',
        'notifications' => 'App\\Notifications\\',
        'listeners' => 'App\\Listeners\\',
        'policies' => 'App\\Policies\\',
        'rules' => 'App\\Rules\\',
        'providers' => 'App\\Providers\\',
    ],

    /*
    |--------------------------------------------------------------------------
    | Skip Namespaces
    |--------------------------------------------------------------------------
    |
    | These namespaces will be skipped during exploration to focus on your
    | application code rather than framework internals.
    |
    */
    'skip_namespaces' => [
        'Illuminate\\',
        'Symfony\\',
        'Psr\\',
        'Carbon\\',
        'Monolog\\',
        'Laravel\\',
    ],

    /*
    |--------------------------------------------------------------------------
    | Maximum Exploration Depth
    |--------------------------------------------------------------------------
    |
    | Controls how deep the dependency exploration goes. Higher values give
    | more complete results but take longer and may be overwhelming.
    |
    */
    'max_depth' => 3,

    /*
    |--------------------------------------------------------------------------
    | Detection Patterns
    |--------------------------------------------------------------------------
    |
    | Control which dependency patterns are detected in method bodies.
    | Disable patterns you don't need for better performance.
    |
    */
    'detect_patterns' => [
        'auth' => true,           // Auth::user(), auth()->user()
        'static_calls' => true,   // Model::find(), User::create()
        'instantiations' => true, // new Class()
        'events' => true,         // event(), Event::dispatch()
        'jobs' => true,           // dispatch(), Job::dispatch()
        'notifications' => true,  // notify(), Notification::send()
        'views' => true,          // view(), View::make()
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | Safety settings to ensure this tool only runs in appropriate environments.
    |
    */
    'security' => [
        // Only allow in these environments
        'allowed_environments' => ['local', 'development', 'testing'],
        
        // Require debug mode to be enabled
        'require_debug' => true,
    ],
];
