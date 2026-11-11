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
- **VenueDuplicateException** - Thrown when a published venue with the same name and city is within 100 metres

## Data Model

```php
Venue {
    uuid: string (primary key)
    name: string
    city: string
    address: string (nullable)
    latitude: decimal(10,8)
    longitude: decimal(11,8)
    published_at: timestamp (nullable, null until reviewed)
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

### Publishing
`PublishedVenueScope` hides venues with no `published_at`. Seeded and imported venues are published when they're created. Venues created by `VenueService::createVenue()` (a report submission naming a new venue, or `POST /api/venues`) start unpublished, and `ReportService::reviewReport()` publishes one when it publishes a report about it. A venue an admin unpublished has an `unpublished_at` date, and `reviewReport()` refuses to publish reports about it (throwing `VenueUnpublishedException`) until the venue is published again. If an unpublished venue with the same name and city already exists within 100 metres, `createVenue()` returns it instead of creating a copy, and `POST /api/venues` only returns the venue's `uuid`, so neither shows whether a hidden venue existed. Code that needs every venue, such as the review queue, opts out with `withoutGlobalScope(PublishedVenueScope::class)`.

### Matching a submission to a venue
A name and city isn't unique, because chains have many branches in one city. `VenueService::findSameVenue()` decides which venue a submission means. With coordinates, it's a venue with the same name and city (ignoring case and surrounding spaces) within `VenueService::SAME_VENUE_RADIUS_KM` (100 metres), the published one first, then the nearest. Without coordinates, the name and city only match when exactly one venue has them. A report naming a chain with no coordinates gets a new unpublished venue at `ReportService::DEFAULT_LATITUDE` and `DEFAULT_LONGITUDE`, which the review queue flags so an admin can place it. The admin edit form only refuses a name and city another venue has when the name or city actually changes, so a branch can still be moved.

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
