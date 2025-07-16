# DaveKoala: Laravel Archaeologist

🔍 **Explore Laravel routes and their complete dependency chains**

As a new developer on the team of an already established Laravel application it is tricky to visualise what is going on.

This is stage one of my Laravel Archaeologist Tools. Developer tooling that allows us to view and search for routes and explore the code behind them. Middleware, classes, traits, etc.

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
