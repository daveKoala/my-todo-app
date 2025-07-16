<?php

return [
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
];
