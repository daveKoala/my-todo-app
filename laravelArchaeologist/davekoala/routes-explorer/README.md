# DaveKoala: Laravel Archaeologist

🔍 **Explore Laravel routes and their complete dependency chains**

## Overview

Once installed open `http://127.0.0.1/dev/routes-explorer`

Laravel Archaeologist is a development tool designed to help new team members and existing developers explore and understand large Laravel applications.

![Screen shot of routes and graph](<resources/views/images/Screenshot 2025-07-16 at 10.17.29.png>)

It provides a clear, searchable interface to view:

-   Routes
-   Controllers
-   Models
-   Middleware
-   Traits
-   Class relationships

It also visualizes these relationships to make onboarding and refactoring easier.

## Why?

When joining an existing Laravel project, understanding "what connects to what" can be painful.
This tool makes that process easier by giving you immediate visibility into the routing structure and the classes behind it.

### Patterns

**How we discover class, traits, middleware, etc**

-   Regex matches a pattern in the source code string
-   Extract the potential class name from the match
-   Build the full class name (usually by prepending App\Whatever\)
-   Check if that class actually exists with class_exists()
-   Add to dependencies array if it exists

Here's the general pattern broken down:

```php
// The pattern template:

if (preg_match_all('/PATTERN/', $source, $matches)) {
    foreach ($matches[1] as $capturedName) {
        // Build full class name
        $fullClass = "App\\Namespace\\{$capturedName}";
        // Verify it exists
        if (class_exists($fullClass)) {
            $dependencies[] = [
                'class' => $fullClass,
                'pattern' => 'what was matched',
                'usage' => 'type_of_usage'
            ];
        }
    }
}
```

So for example:

-   dispatch(new SendEmailJob()) → captures "SendEmailJob" → checks if `App\Jobs\SendEmailJob` exists
-   event(new OrderPlaced()) → captures "OrderPlaced" → checks if `App\Events\OrderPlaced` exists
-   User::create() → captures "User" → checks if `App\Models\User` exists

The `class_exists()` check is crucial because it prevents false positives. Without it, you might catch things like `dispatch(new DateTime())` and try to analyze `App\Jobs\DateTime` which doesn't exist.

## Todo

### House keeping

-   Add configuration for things like 'max depth' list of classes to ignore
-   The Patterns in `ClassAnalysisEngine.php` as becoming too many, might be time to refactor
-   Test, now I know what it is I am building time to firm up the logic
-   Make sure this only runs in `development`mode

### Next steps / feature

-   Add functionality to explore classes directly? Form to take full namespace and class name and(?) method?
-   How to turn this into a Composer package and publish? Time to show to people?

## Contributing / Feedback

If this sounds useful, or you want to contribute ideas, feel free to raise an issue or reach out.
