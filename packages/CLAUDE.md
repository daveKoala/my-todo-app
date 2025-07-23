# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This repository contains **DaveKoala Routes Explorer** - a Laravel package for analyzing Laravel application routes and their complete dependency chains. It's designed as a development tool to help developers understand large Laravel applications by visualizing routes, controllers, models, middleware, traits, and their relationships.

## Architecture

### Core Components

- **RoutesExplorerServiceProvider** (`src/RoutesExplorerServiceProvider.php`) - Laravel service provider that registers routes, views, and publishes configuration
- **ClassAnalysisEngine** (`src/Explorer/ClassAnalysisEngine.php`) - Main analysis engine that explores Laravel routes and dependencies using reflection and pattern matching
- **ClassResolver** (`src/Explorer/ClassResolver.php`) - Laravel-native class resolution without autoloader hacks, with configurable namespace support
- **RoutesExplorerController** (`src/Http/Controllers/RoutesExplorerController.php`) - Web controller that provides route listing and detailed route analysis via AJAX
- **Pattern Classes** (`src/Explorer/patterns/`) - Individual pattern detection classes for different Laravel constructs (Auth, Models, Jobs, Events, etc.)

### Key Features

The system uses a combination of:
1. **Reflection-based analysis** - For constructor dependencies, method parameters, and class relationships
2. **Source code pattern matching** - Regex patterns to detect runtime dependencies like `Auth::user()`, `dispatch(new Job())`, `Model::create()`, etc.
3. **Recursive dependency exploration** - Follows the dependency chain with configurable depth limits

### Pattern Detection System

The ClassAnalysisEngine uses individual pattern classes in `src/Explorer/patterns/` to detect different types of dependencies:
- **AuthUsers.php** - Detects `Auth::user()` calls
- **SimpleModelReferences.php** - Detects `Model::method()` static calls
- **JobDispatching.php** - Detects `dispatch(new JobClass())`
- **EventDispatch.php** - Detects `event(new EventClass())`
- **NotificationSending.php** - Detects notification patterns
- **ExplicitModelStatic.php** - Detects explicit model static calls
- **NewClassName.php** - Detects `new ClassName()` instantiations

Each pattern class implements a `detect(string $source)` method that returns dependency arrays.

## Development Commands

This is a Laravel package, so standard Laravel development practices apply:

### Package Installation
The package is designed to be installed as a local package in a Laravel application. It registers automatically via Laravel's package discovery.

### Configuration Publishing
To customize namespaces and analysis settings:
```bash
php artisan vendor:publish --provider="DaveKoala\RoutesExplorer\RoutesExplorerServiceProvider" --tag="config"
```

### Accessing the Tool
Once installed, access the routes explorer at: `http://127.0.0.1/dev/routes-explorer`

### Key Files to Understand
- `composer.json` - Package configuration with PSR-4 autoloading
- `config/routes-explorer.php` - Configuration for namespaces, analysis depth, and security settings
- `src/RoutesExplorerServiceProvider.php` - Package registration and config publishing
- `src/Explorer/ClassAnalysisEngine.php` - Core analysis logic
- `src/Explorer/ClassResolver.php` - Laravel-native class resolution with configurable namespaces
- `src/Http/Controllers/RoutesExplorerController.php` - Web interface controller

## Class Resolution Architecture

The package uses Laravel-native class resolution through the `ClassResolver` class:

1. **Configuration-driven namespaces** - Supports custom namespaces beyond `App\`
2. **Laravel autoloader integration** - Uses `ReflectionClass` instead of manual file loading
3. **Portable across Laravel setups** - Works with monorepos, custom folder structures, and Docker environments
4. **No autoloader hacks** - Relies on Composer's PSR-4 autoloading and Laravel's class resolution

## Important Notes

- The tool is intended for **development environments only**
- Uses reflection and file system access to analyze code
- Includes depth limits (default: 3-5 levels) to prevent infinite recursion
- Pattern matching uses `class_exists()` validation to prevent false positives
- All dependency detection validates that classes actually exist before reporting them

## Views and Frontend

- Views are located in `resources/views/` using Blade templating
- Main interface: `routes-explorer.blade.php`
- AJAX-driven route analysis with JSON responses
- Includes CSS styling in `partials/styles.blade.php`