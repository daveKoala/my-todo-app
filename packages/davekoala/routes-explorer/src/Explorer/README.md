# Laravel Route Explorer

🔍 **Explore Laravel routes and their complete dependency chains**

Aa Artisan command that analyses Laravel routes and maps out all related classes, dependencies, and relationships. Perfect for understanding complex codebases, onboarding new developers, or performing code archaeology on legacy projects.

## ✨ Features

-   **Complete route mapping** - From route to controller to models and beyond
-   **Dependency chain analysis** - Shows constructor and method dependencies
-   **Class relationship mapping** - Inheritance, interfaces, traits, and composition
-   **Multiple output formats** - Table, JSON, and tree views
-   **Laravel-aware** - Understands Models, Controllers, and Eloquent relationships
-   **Framework filtering** - Focuses on your app code, skips framework classes
-   **Recursion control** - Configurable depth limits prevent infinite loops

## Quick Start

### Basic Usage

```bash
# Explore a route by name
php artisan explore:route notes.show

# Explore a route by URI
php artisan explore:route "notes/{note}"

# With custom depth
php artisan explore:route notes.show --depth=5
```

### Finding Routes

First, see what routes are available in your application:

```bash
# List all routes
php artisan route:list

# Filter routes by method
php artisan route:list --method=GET
php artisan route:list --method=POST

# Filter routes by name pattern
php artisan route:list --name=notes

# Filter routes by URI pattern
php artisan route:list --path=api

# Show only specific columns
php artisan route:list --columns=method,uri,name,action
```

### Route Explorer Examples

```bash
# Explore by route name (recommended)
php artisan explore:route notes.show
php artisan explore:route notes.create
php artisan explore:route api.users.index

# Explore by URI pattern
php artisan explore:route "users/{user}/posts"
php artisan explore:route "api/notes"

# Different output formats
php artisan explore:route notes.show --format=json
php artisan explore:route notes.show --format=tree
php artisan explore:route notes.show --format=table  # default

# Control exploration depth
php artisan explore:route notes.show --depth=1  # Shallow
php artisan explore:route notes.show --depth=5  # Deep
```

## 📊 Output Formats

### Table Format (Default)

Shows a comprehensive table with all discovered classes:

|--------|----------------|--------------|------------------------------------|------------|--------|--------------|
| Class | Type | Context | Extends | Interfaces | Traits | Dependencies |
|-------------------------------------|----------------|--------------|------------------------------------|------------|--------|--------------|
| App\Http\Controllers\NoteController | Controller | Controller | App\Http\Controllers\Controller | 0 | 0 | 1 |
| App\Http\Controllers\Controller | Controller | Parent Class | | 0 | 0 | 0 |
| App\Models\Note | Eloquent Model | Dependency | Illuminate\Database\Eloquent\Model | 9 | 2 | 0 |
|-------------------------------------|----------------|--------------|------------------------------------|------------|--------|--------------|

### JSON Format

Machine-readable output for integration with other tools:

```bash
php artisan explore:route notes.show --format=json
```

### Tree Format

Hierarchical view showing exploration depth:

```bash
php artisan explore:route notes.show --format=tree
```

## What Gets Analysed

### Route Information

-   URI and HTTP methods
-   Route name and action
-   Applied middleware
-   Route parameters

### Class Relationships

-   **Inheritance** - Parent classes and inheritance chains
-   **Interfaces** - Implemented interfaces
-   **Traits** - Used traits and their methods
-   **Composition** - Injected dependencies

### Dependencies

-   **Constructor injection** - Classes injected into constructors
-   **Method injection** - Classes injected into route methods
-   **Type hints** - All non-built-in type hints analysed

### Laravel-Specific Features

-   ✅ **Eloquent Models** - Automatic model detection
-   ✅ **Model Relationships** - HasOne, HasMany, BelongsTo, etc.
-   ✅ **Controller Actions** - Method-specific analysis
-   ✅ **Service Container** - Dependency injection patterns

## 🏗️ Project Structure

This command is organized as a self-contained package within the Laravel application:

```
app/Console/Commands/ExploreRouteCommand/
├── ExploreRouteCommand.php        # Main command orchestration
├── RouteDiscovery.php             # Route finding and validation
├── ClassAnalysisEngine.php        # Core analysis logic with recursion
├── TypeDetectors.php              # Class type detection & Laravel patterns
├── StringHelpers.php              # Utility functions
└── OutputFormatters.php           # Table, JSON, tree output formats
```

## Configuration

### Depth Control

Control how deep the analysis goes:

```bash
--depth=1    # Only direct dependencies
--depth=2    # Default - good balance
--depth=3    # Deeper analysis
--depth=5    # Very thorough (may be slow)
```

### Framework Filtering (Todo)

The explorer automatically skips Laravel framework classes to focus on your application code. Currently filters:

## 📝 Example Output

### Route: `notes.show`

```
🔍 Exploring Laravel route: notes.show
============================================================

📍 Route Information:
┌──────────┬─────────────────────────────────────────┐
│ Property │ Value                                   │
├──────────┼─────────────────────────────────────────┤
│ URI      │ notes/{note}                           │
│ Name     │ notes.show                             │
│ Methods  │ GET|HEAD                               │
│ Action   │ App\Http\Controllers\NoteController@show │
│ Middleware│ web                                    │
└──────────┴─────────────────────────────────────────┘

🔗 Exploring route chain...

🎯 Starting from: App\Http\Controllers\NoteController@show

📦 🎮 App\Http\Controllers\NoteController Controller
  ↗️  Extends: App\Http\Controllers\Controller
  🔧 Method: show()
    💉 Injected: note: App\Models\Note
      📦 🗄️ App\Models\Note Dependency
        ↗️  Extends: Illuminate\Database\Eloquent\Model
        🗄️  Exploring Model relationships...
          🔗 user(): BelongsTo
            📦 🗄️ App\Models\User Related Model (BelongsTo)
          🔗 tags(): BelongsToMany
            📦 🗄️ App\Models\Tag Related Model (BelongsToMany)
```

## 🎯 Use Cases

### 🆕 **New Developer Onboarding**

```bash
# Understand how user management works
php artisan explore:route users.show

# See what's involved in creating content
php artisan explore:route posts.store
```

### 🔍 **Code Archaeology**

```bash
# Explore legacy functionality
php artisan explore:route admin.reports.generate --depth=5

# Understand complex business logic
php artisan explore:route api.orders.process
```

### 🏗️ **Architecture Analysis**

```bash
# Map out API endpoints
php artisan route:list --path=api
php artisan explore:route api.users.index

# Understand dependency patterns
php artisan explore:route dashboard --format=tree
```

### 🐛 **Debugging & Troubleshooting**

```bash
# See what classes are involved in a problematic feature
php artisan explore:route payments.process

# Understand the full chain for error tracking
php artisan explore:route api.webhooks.stripe --format=json
```

## 🚀 Tips & Best Practices

### Finding the Right Route

1. **Start with route listing**: `php artisan route:list`
2. **Filter by feature**: `php artisan route:list --name=users`
3. **Use route names**: More reliable than URIs
4. **Check middleware**: Routes with `auth` middleware are often interesting

### Optimal Depth Settings

-   **`--depth=1`**: Quick overview, immediate dependencies only
-   **`--depth=2`**: Default, good for most exploration
-   **`--depth=3`**: Thorough analysis without going too deep
-   **`--depth=4+`**: Use sparingly, can become overwhelming

### Output Format Selection

-   **Table**: Best for overview and sharing with team
-   **JSON**: Perfect for automation and further processing
-   **Tree**: Great for understanding exploration flow

## 🔮 Future Enhancements

Planned features for future versions:

-   🌐 **Web interface** with interactive node graphs
-   📦 **Standalone package** for easy installation

## 🤝 Contributing

This tool is currently embedded within the Laravel application but is designed for easy extraction into a standalone package. The modular structure in `app/Console/Commands/ExploreRouteCommand/` makes it simple to enhance and extend.
