# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This repository contains **DaveKoala Routes Explorer** - a Laravel package for analyzing Laravel application routes and their complete dependency chains. It's designed as a development tool to help developers understand large Laravel applications by visualizing routes, controllers, models, middleware, traits, and their relationships.

## Architecture

### Core Components

- **RoutesExplorerServiceProvider** (`src/RoutesExplorerServiceProvider.php`) - Laravel service provider that registers routes and views
- **ClassAnalysisEngine** (`src/Explorer/ClassAnalysisEngine.php`) - Main analysis engine that explores Laravel routes and dependencies using reflection and pattern matching
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

### Accessing the Tool
Once installed, access the routes explorer at: `http://127.0.0.1/dev/routes-explorer`

### Key Files to Understand
- `composer.json` - Package configuration with PSR-4 autoloading
- `src/RoutesExplorerServiceProvider.php` - Package registration
- `src/Explorer/ClassAnalysisEngine.php` - Core analysis logic (450+ lines)
- `src/Http/Controllers/RoutesExplorerController.php` - Web interface controller

## Autoloader Considerations

The package includes special autoloader handling to ensure it can access application classes from within the package context. Both `ClassAnalysisEngine.php` and `RoutesExplorerController.php` include `ensureAutoloaderAccess()` methods that:

1. Load the application's composer autoloader
2. Register custom autoloader for App\ namespace classes  
3. Pre-load common Laravel classes that often cause issues

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