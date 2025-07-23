# Routes Explorer Tests

Essential tests for the DaveKoala Routes Explorer package.

## Test Structure

```
tests/
├── TestCase.php                    # Base test class with package setup
├── Unit/                          # Unit tests for core classes
│   ├── ClassResolverTest.php       # Core class resolution functionality
│   └── RoutesExplorerSecurityTest.php # Security middleware tests
└── Feature/                       # Integration tests
    └── BasicIntegrationTest.php    # Basic end-to-end functionality
```

## Running Tests

```bash
# Install dependencies
composer install --dev

# Run all tests
composer test

# Run specific test types
phpunit tests/Unit      # Unit tests only
phpunit tests/Feature   # Integration tests only
```

## Test Coverage

### Essential Areas Covered
- **Class Resolution**: Core functionality for finding and loading classes
- **Security**: Environment and debug mode restrictions
- **Integration**: Basic end-to-end route analysis

### What We Don't Test
- Pattern detection details (implementation details)
- Complex edge cases (YAGNI)
- Extensive configuration scenarios (over-engineering)

This focused approach ensures the critical functionality works while keeping tests maintainable.