# Shared Module

The Shared module contains common traits, services, and exceptions used across multiple Drink Safe modules.

## Purpose

This module provides:
- Reusable traits for cross-cutting concerns
- Shared services used by multiple modules
- Base exception classes for consistent error handling

## Key Components

### Traits
- **HasUuid** - Automatically generates UUID primary keys for models

### Services
- **GeolocationService** - Distance calculations and geospatial utilities

### Exceptions
- **DrinkSafeException** - Base exception class for all module-specific exceptions

## Traits

### HasUuid
Applied to models that use UUID primary keys instead of auto-incrementing integers.

**Features:**
- Automatically generates UUID on model creation
- Overrides `getKeyType()` to return 'string'
- Overrides `getIncrementing()` to return false

**Usage:**
```php
use DrinkSafe\Shared\Traits\HasUuid;

class Venue extends Model
{
    use HasUuid;

    // Model will now use UUID primary key
}
```

## Services

### GeolocationService
Provides geospatial calculations for venue proximity searches.

**Methods:**
- `distance($lat1, $lng1, $lat2, $lng2): float` - Calculate distance between two coordinates (Haversine formula)
- `isWithinRadius($lat1, $lng1, $lat2, $lng2, $radiusKm): bool` - Check if location is within radius
- `getBoundingBox($lat, $lng, $radiusKm): array` - Get bounding box for query optimisation

**Usage:**
```php
$geoService = app(GeolocationService::class);
$distance = $geoService->distance(51.5074, -0.1278, 51.5074, -0.0014); // London coordinates
```

## Exceptions

### DrinkSafeException
Base exception class that all module-specific exceptions should extend.

**Purpose:**
- Provides consistent error handling across modules
- Enables catching all Drink Safe-related exceptions
- Supports custom HTTP status codes and error messages

**Usage:**
```php
namespace DrinkSafe\Venues\Exceptions;

use DrinkSafe\Shared\Exceptions\DrinkSafeException;

class VenueNotFoundException extends DrinkSafeException
{
    public function __construct(string $uuid)
    {
        parent::__construct(
            sprintf('Venue with UUID %s not found', $uuid),
            404
        );
    }
}
```

## Development Guidelines

- Keep shared code minimal and truly reusable
- Document all public methods with comprehensive docblocks
- Maintain backward compatibility when modifying shared code
- Write unit tests for all shared services and traits
