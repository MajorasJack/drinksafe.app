# Drink Safe Application Module

This directory contains the modular implementation of the Drink Safe anonymous venue reporting platform.

## Architecture

Drink Safe follows a domain-driven design pattern with clear module boundaries:

- **Venues** - Venue management, search, and geolocation functionality
- **Reports** - Report submission, filtering, and moderation
- **Shared** - Common traits, services, and exceptions used across modules

## Module Structure

Each module follows a consistent directory structure:

```
ModuleName/
├── Models/           # Eloquent models
├── Controllers/      # HTTP controllers (thin, delegate to services)
├── Services/         # Business logic layer
├── Policies/         # Authorization policies
├── Requests/         # FormRequest validation classes
├── Resources/        # API Resources for JSON responses
├── Enums/            # Enums (if applicable)
├── Exceptions/       # Module-specific exceptions
└── Tests/
    ├── Feature/      # Feature tests (controllers, endpoints)
    └── Unit/         # Unit tests (models, services, scopes)
```

## Key Principles

1. **Separation of Concerns**: Business logic in services, not controllers
2. **Testability**: All services and models have comprehensive test coverage
3. **Type Safety**: Full PHP typehints and return types
4. **Explicit Errors**: Custom exceptions for clear debugging
5. **UUID Primary Keys**: For security and anonymity

## Namespace Convention

All classes use the `DrinkSafe\{Module}\{Subdirectory}` namespace pattern.

Example: `DrinkSafe\Venues\Services\VenueService`

## Development Guidelines

- Use descriptive names for variables and methods
- Follow Laravel conventions and best practices
- Add comprehensive docblocks for all public methods
- Write tests before or alongside implementation
- Use factory classes for test data generation

## Documentation

For detailed architectural decisions, see:
- `/docs/architecture/modular-structure.md`
- `/docs/architecture/naming-conventions.md`
- `/docs/architecture/data-flow.md`
