# Venues Module

The Venues module handles all venue-related functionality including venue management, search, filtering, and geolocation queries.

## Purpose

This module manages:
- Venue creation and retrieval
- Geospatial search (find venues within radius)
- Full-text search by name and city
- Venue-report relationships
- Venue statistics (report counts, etc.)

## Key Components

### Models
- **Venue** - Eloquent model representing a venue with geolocation data

### Controllers
- **VenueController** - RESTful venue operations (index, show, store)
- **VenueSearchController** - Search and filter venues

### Services
- **VenueService** - Core venue CRUD operations
- **VenueSearchService** - Search, filter, and geospatial queries

### Resources
- **VenueResource** - JSON response formatting for single venue
- **VenueCollection** - Paginated venue collection responses

### Exceptions
- **VenueNotFoundException** - Thrown when venue UUID not found
- **VenueDuplicateException** - Thrown when duplicate name+city detected

## Data Model

```php
Venue {
    uuid: string (primary key)
    name: string
    city: string
    address: string (nullable)
    latitude: decimal(10,8)
    longitude: decimal(11,8)
    created_at: timestamp
    updated_at: timestamp

    // Relationships
    reports: Report[] (hasMany)
}
```

## Key Features

### Geospatial Search
The `VenueSearchService::nearby()` method finds venues within a specified radius using geospatial calculations.

### Full-Text Search
Venues can be searched by name or city with partial matching support.

### Report Counts
Venues automatically load report counts for display in lists and maps.

## Usage Examples

### Retrieve All Venues
```php
$venueService = app(VenueService::class);
$venues = $venueService->getAllVenues();
```

### Search Venues
```php
$searchService = app(VenueSearchService::class);
$results = $searchService->search('nightclub');
```

### Find Nearby Venues
```php
$nearby = $searchService->nearby(51.5074, -0.1278, 10); // London, 10km radius
```
