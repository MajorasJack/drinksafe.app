# Reports Module

The Reports module handles all report-related functionality including report submission, filtering, moderation, and retrieval.

## Purpose

This module manages:
- Anonymous report submission
- Report filtering by date, time, and venue
- PII detection and content moderation
- Report retrieval with pagination
- Soft deletion for moderation purposes

## Key Components

### Models
- **Report** - Eloquent model representing an incident report with soft deletes

### Controllers
- **ReportController** - RESTful report operations (index, show, store)
- **ReportFilterController** - Filter reports by various criteria

### Services
- **ReportService** - Core report CRUD and filtering operations
- **ReportModerationService** - PII detection and content sanitisation

### Enums
- **TimeOfDay** - Enum for incident timing (Morning, Afternoon, Evening, Night, Unknown)

### Resources
- **ReportResource** - JSON response formatting for single report
- **ReportCollection** - Paginated report collection responses

### Exceptions
- **ReportNotFoundException** - Thrown when report UUID not found
- **ReportValidationException** - Thrown when report data validation fails

## Data Model

```php
Report {
    uuid: string (primary key)
    venue_uuid: string (foreign key)
    incident_date: date
    time_of_day: TimeOfDay enum
    description: text
    created_at: timestamp
    updated_at: timestamp
    deleted_at: timestamp (soft delete)

    // Relationships
    venue: Venue (belongsTo)
}
```

## Key Features

### Anonymous Submission
Reports are submitted without user accounts or personal information.

### PII Detection
The `ReportModerationService` automatically detects and rejects reports containing:
- Email addresses
- Phone numbers
- Potential personal identifiers

### Filtering
Reports can be filtered by:
- Venue UUID
- Date range (start_date, end_date)
- Time of day (Morning, Afternoon, Evening, Night, Unknown)
- Recency (most recent reports)

### Soft Deletes
Reports use soft deletion for moderation and compliance purposes. Deleted reports can be reviewed or restored by administrators.

## Usage Examples

### Submit a Report
```php
$reportService = app(ReportService::class);
$report = $reportService->createReport([
    'venue_uuid' => 'abc-123-def',
    'incident_date' => '2024-01-15',
    'time_of_day' => 'Night',
    'description' => 'Witnessed suspicious behaviour near the bar area.',
]);
```

### Get Recent Reports
```php
$recent = $reportService->getRecentReports(10); // Last 10 reports
```

### Filter by Date Range
```php
$filtered = $reportService->filterByDateRange(
    Carbon::parse('2024-01-01'),
    Carbon::parse('2024-01-31')
);
```
